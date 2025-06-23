<?php

namespace MediaWiki\Extension\LinkTarget;

use DOMXPath;
use HtmlFormatter\HtmlFormatter;
use MediaWiki\Hook\OutputPageParserOutputHook;
use OutputPage;
use ParserOutput;

class LinkTargetHooks implements OutputPageParserOutputHook {
	/**
	 * @param OutputPage $out
	 * @param ParserOutput $parseroutput
	 */
	public function onOutputPageParserOutput( $out, $parseroutput ): void {
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

		$text = $parseroutput->getRawText();
		if ( !$text ) {
			return;
		}

		/** @todo Support framename? */
		$validTargets = [ '_blank', '_self', '_parent', '_top' ];

		/**
		 * this is only needed for 1.39 and down since in 1.43 a different way is introduced
		 * <meta property="mw:PageProp/toc" />
		 * instead of
		 * <mw:tocplace></mw:tocplace>
		 *
		 * Work around HtmlFormatter's handling of MediaWiki TOC elements.
		 *
		 * HtmlFormatter->getDoc() uses loadHTML() which replaces <mw:tocplace></mw:tocplace>
		 * to <tocplace></tocplace>, stripping the namespace prefix. This breaks the parser's
		 * ability to locate and replace TOC markers with actual table of contents.
		 *
		 * To prevent this, we pre-process the text by replacing TOC markers with the
		 * generated TOC HTML before passing it to HtmlFormatter.
		 */
		if ( method_exists( $parseroutput, 'getTOCHTML' ) ) {
			// @phan-suppress-next-line PhanUndeclaredMethod
			$tocHtml = $parseroutput->getTOCHTML();
			$text = \Parser::replaceTableOfContentsMarker( $text, $tocHtml );
		}
		$htmlFormatter = new HtmlFormatter( $text );
		$dom = $htmlFormatter->getDoc();
		$xpath = new DOMXpath( $dom );
		foreach ( $linkTargetParentClasses as $target => $parentClasses ) {
			if ( !is_array( $parentClasses ) ) {
				$parentClasses = [ $parentClasses ];
			}
			foreach ( $parentClasses as $parentClass ) {
				if ( !in_array( $target, $validTargets, true ) ) {
					$target = $linkTargetDefault;
				}
				$nodes = $xpath->query(
					"//*[contains(concat(' ', normalize-space(@class), ' '), ' {$parentClass} ')]//a" );
				foreach ( $nodes as $node ) {
					$node->setAttribute( 'target', $target );
				}
			}
		}
		$parseroutput->setText( $htmlFormatter->getText() );
	}
}
