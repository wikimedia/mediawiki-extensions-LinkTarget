<?php

namespace MediaWiki\Extension\LinkTarget;

use MediaWiki\Hook\OutputPageBeforeHTMLHook;
use OutputPage;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

class LinkTargetHooks implements OutputPageBeforeHTMLHook {
	/**
	 * @param OutputPage $out
	 * @param string &$text
	 */
	public function onOutputPageBeforeHTML( $out, &$text ): void {
		$config = $out->getConfig();
		$linkTargetParentClasses = $config->get( 'LinkTargetParentClasses' );
		$linkTargetDefault = $config->get( 'LinkTargetDefault' );

		if ( !$linkTargetParentClasses ) {
			// Save all that processing
			return;
		}

		if ( !is_array( $linkTargetParentClasses ) ) {
			$linkTargetParentClasses = [ $linkTargetParentClasses ];
		}

		if ( !$text ) {
			return;
		}

		/** @todo Support framename? */
		$validTargets = [ '_blank', '_self', '_parent', '_top' ];

		$dom = DOMUtils::parseHTML( $text );
		foreach ( $linkTargetParentClasses as $target => $parentClasses ) {
			if ( !is_array( $parentClasses ) ) {
				$parentClasses = [ $parentClasses ];
			}
			foreach ( $parentClasses as $parentClass ) {
				if ( !in_array( $target, $validTargets, true ) ) {
					$target = $linkTargetDefault;
				}
				$nodes = DOMCompat::querySelectorAll( $dom, ".{$parentClass} a" );
				foreach ( $nodes as $node ) {
					$node->setAttribute( 'target', $target );
				}
			}
		}
		$text = DOMCompat::getInnerHTML( DOMCompat::getBody( $dom ) );
	}
}
