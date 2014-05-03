<?php

/**
 * Bean tests, tests confusion of aliased lists.
 *
 * @file    RedUNIT/Base/Bean.php
 * @desc    Tests confusion of aliased lists.
 * @author  Gabor de Mooij and the RedBeanPHP Community
 * @license New BSD/GPLv2
 *
 * (c) G.J.G.T. (Gabor) de Mooij and the RedBeanPHP Community.
 * This source file is subject to the New BSD/GPLv2 License that is bundled
 * with this source code in the file license.txt.
 */
class RedUNIT_Base_Bean extends RedUNIT_Base
{
	/**
	 * Test whether aliased list causes other list to be
	 * affected. (issue found when developing RB4).
	 * 
	 * @return void
	 */
	public function testBean()
	{
		R::nuke();
		$book = R::dispense( 'book' );
		$pages = R::dispense( 'page', 2 );
		$ads = R::dispense('ad', 3 );
		$tags = R::dispense( 'tag', 2 );
		$author = R::dispense( 'author' );
		$coauthor = R::dispense( 'author' );
		$book->alias( 'magazine' )->ownAd = $ads;
		$book->ownPage = $pages;
		$book->sharedTag = $tags;
		$book->via( 'connection' )->sharedUser = array( R::dispense( 'user' ) );
		$book->author = $author;
		$book->coauthor = $coauthor;
		R::store( $book );
		$book = $book->fresh();
		asrt( count($book->ownPage), 2 );
		asrt( count($book->alias('magazine')->ownAd), 3 );
		$book->ownAd = array();
		array_pop( $book->ownPage );
		R::store( $book );
		$book = $book->fresh();
		asrt( count($book->ownPage), 1 );
		asrt( count($book->alias('magazine')->ownAd), 0 );
	}
}