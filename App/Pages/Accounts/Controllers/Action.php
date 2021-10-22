<?php

namespace FSPoster\App\Pages\Accounts\Controllers;

use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Request;

class Action
{
	public function get_fb_accounts ()
	{
		$accounts_list  = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND node_type='ownpage' AND (user_id=%d OR is_public=1)) ownpages,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND node_type='group' AND (user_id=%d OR is_public=1)) `groups`,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1
	WHERE (user_id=%d OR is_public=1) AND driver='fb' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );
		$my_accounts_id = [ -1 ];

		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
				(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`,
				(SELECT name FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.account_id AND node_id=tb1.poster_id AND user_id=%d) poster_name
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='fb' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_fb_pages ( $params = [] )
	{
		if ( ! ( isset( $params[ 'account_id' ] ) && ! empty( $params[ 'account_id' ] && isset( $params[ 'group_id' ] ) && ! empty( $params[ 'group_id' ] ) ) ) )
		{
			return [];
		}

		$group_id = $params[ 'group_id' ];

		$get_group = DB::DB()->get_row( DB::DB()->prepare( "SELECT * FROM " . DB::table( 'account_nodes' ) . " WHERE id = %d AND node_type = 'group' AND ( user_id = %d OR is_public = 1 ) AND blog_id = %d", [
			$group_id,
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		if ( ! $get_group )
		{
			return [];
		}

		$poster_id = isset( $get_group[ 'poster_id' ] ) && $get_group[ 'poster_id' ] > 0 ? $get_group[ 'poster_id' ] : NULL;

		$account_id = $params[ 'account_id' ];
		$pages      = [];

		$get_account = DB::DB()->get_row( DB::DB()->prepare( 'SELECT * FROM ' . DB::table( 'accounts' ) . ' WHERE id = %d', [ $account_id ] ), ARRAY_A );

		$pages[] = [
			'id'       => '',
			'name'     => $get_account[ 'name' ],
			'selected' => is_null( $poster_id )
		];

		$get_pages = DB::DB()->get_results( DB::DB()->prepare( "SELECT * FROM " . DB::table( 'account_nodes' ) . " WHERE account_id = %d AND node_type = 'ownpage' AND ( user_id = %d OR is_public = 1 ) AND blog_id = %d", [
			$account_id,
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		foreach ( $get_pages as $page )
		{
			$pages[] = [
				'id'       => $page[ 'id' ],
				'name'     => $page[ 'name' ],
				'selected' => ! is_null( $poster_id ) && $page[ 'node_id' ] == $poster_id
			];
		}

		return $pages;
	}

	public function get_google_b_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id) locations,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden` 
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='google_b' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='google_b' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_blogger_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
	 	*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) AS `blogs`,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1
	WHERE (user_id=%d OR is_public=1) AND driver='blogger' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND blog_id=%d AND account_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				Helper::getBlogId(),
				$account_info[ 'id' ]
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='blogger' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_instagram_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden` 
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='instagram' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$accounts_with_cookie = DB::DB()->get_results( "SELECT * FROM " . DB::table( 'account_sessions' ) . " WHERE driver='instagram'", 'ARRAY_A' );
		$cookie_users         = [];

		foreach ( $accounts_with_cookie as $record )
		{
			$cookie_users[] = $record[ 'username' ];
		}

		foreach ( $accounts_list as $i => $account )
		{
			$accounts_list[ $i ][ 'has_cookie' ] = in_array( $account[ 'username' ], $cookie_users ) ? 1 : 0;
		}

		if ( version_compare( PHP_VERSION, '5.6.0' ) < 0 )
		{
			echo '<div >
				<div ><i class="fa fa-warning fa-exclamation-triangle fa-5x" ></i> </div>
				<div >For using instagram account, please update your PHP version 5.6 or higher!</div>
				<div>Your current PHP version is: ' . PHP_VERSION . '</div>
			</div>';

			return [];
		}

		return [
			'accounts_list' => $accounts_list
		];
	}

	public function get_linkedin_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
	 	*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) AS companies,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1
	WHERE (user_id=%d OR is_public=1) AND driver='linkedin' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='linkedin' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_medium_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) publications,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden` 
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='medium' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND blog_id=%d AND account_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				Helper::getBlogId(),
				$account_info[ 'id' ]
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='medium' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_ok_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
	 	*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) AS `groups`,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1
	WHERE (user_id=%d OR is_public=1) AND driver='ok' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND blog_id=%d AND account_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				Helper::getBlogId(),
				$account_info[ 'id' ]
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='ok' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_pinterest_accounts ()
	{
		$accountsList = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) boards,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='pinterest' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$accounts_with_cookie = DB::DB()->get_results( "SELECT * FROM " . DB::table( 'account_sessions' ) . " WHERE driver='pinterest'", 'ARRAY_A' );
		$cookie_users         = [];

		foreach ( $accounts_with_cookie as $record )
		{
			$cookie_users[] = $record[ 'username' ];
		}

		foreach ( $accountsList as $i => $account )
		{
			$accountsList[ $i ][ 'has_cookie' ] = in_array( $account[ 'username' ], $cookie_users ) ? 1 : 0;
		}

		$collectMyAccountIDs = [ -1 ];
		foreach ( $accountsList as $i => $accountInf1 )
		{
			$collectMyAccountIDs[]             = (int) $accountInf1[ 'id' ];
			$accountsList[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$accountInf1[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$publicCommunities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='pinterest' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $collectMyAccountIDs ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accountsList,
			'public_communities' => $publicCommunities
		];
	}

	public function get_reddit_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) subreddits,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden` 
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='reddit' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='reddit' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_telegram_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id) chats,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden` 
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='telegram' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='telegram' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_tumblr_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id) AS blogs,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='tumblr' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='tumblr' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_twitter_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='twitter' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list' => $accounts_list
		];
	}

	public function get_plurk_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='plurk' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list' => $accounts_list
		];
	}

	public function get_vk_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) communities,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='vk' AND `blog_id`=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];

			$accounts_list[ $i ][ 'node_list' ] = DB::DB()->get_results( DB::DB()->prepare( "
			SELECT 
				*,
				(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
			FROM " . DB::table( 'account_nodes' ) . " tb1
			WHERE (user_id=%d OR is_public=1) AND account_id=%d AND blog_id=%d", [
				get_current_user_id(),
				get_current_user_id(),
				get_current_user_id(),
				$account_info[ 'id' ],
				Helper::getBlogId()
			] ), ARRAY_A );
		}

		$public_communities = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT filter_type FROM " . DB::table( 'account_node_status' ) . " WHERE node_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'node' AND user_id = %d) `is_hidden`
	FROM " . DB::table( 'account_nodes' ) . " tb1
	WHERE driver='vk' AND (user_id=%d OR is_public=1) AND blog_id=%d AND account_id NOT IN ('" . implode( "','", $my_accounts_id ) . "')", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		return [
			'accounts_list'      => $accounts_list,
			'public_communities' => $public_communities
		];
	}

	public function get_wordpress_accounts ()
	{
		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "
	SELECT 
		*,
		(SELECT COUNT(0) FROM " . DB::table( 'account_nodes' ) . " WHERE account_id=tb1.id AND (user_id=%d OR is_public=1)) publications,
		(SELECT filter_type FROM " . DB::table( 'account_status' ) . " WHERE account_id=tb1.id AND user_id=%d) is_active,
		(SELECT COUNT(0) FROM " . DB::table( 'grouped_accounts' ) . " WHERE account_id = tb1.id AND account_type = 'account' AND user_id = %d) `is_hidden` 
	FROM " . DB::table( 'accounts' ) . " tb1 
	WHERE (user_id=%d OR is_public=1) AND driver='wordpress' AND blog_id=%d", [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$my_accounts_id = [ -1 ];
		foreach ( $accounts_list as $i => $account_info )
		{
			$my_accounts_id[] = (int) $account_info[ 'id' ];
		}

		return [
			'accounts_list' => $accounts_list
		];
	}

	public function get_fb_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'fb' ] )
		];
	}

	public function get_twitter_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'twitter' ] )
		];
	}

	public function get_plurk_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'plurk' ] )
		];
	}

	public function get_linkedin_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'linkedin' ] )
		];
	}

	public function get_ok_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'ok' ] )
		];
	}

	public function get_pinterest_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'pinterest' ] )
		];
	}

	public function get_reddit_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'reddit' ] )
		];
	}

	public function get_tumblr_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'tumblr' ] )
		];
	}

	public function get_vk_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'vk' ] )
		];
	}

	public function get_google_b_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'google_b' ] )
		];
	}

	public function get_blogger_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'blogger' ] )
		];
	}

	public function get_medium_apps ()
	{
		return [
			'applications' => DB::fetchAll( 'apps', [ 'driver' => 'medium' ] )
		];
	}

	public function get_subreddit_info ()
	{
		$accountId  = (int) Request::post( 'account_id', '0', 'num' );
		$userId     = (int) get_current_user_id();
		$accountInf = DB::DB()->get_row( "SELECT * FROM " . DB::table( 'accounts' ) . " WHERE id='{$accountId}' AND driver='reddit' AND (user_id='{$userId}' OR is_public=1) AND blog_id='" . Helper::getBlogId() . "' ", ARRAY_A );

		return [
			'accountId'  => $accountId,
			'userId'     => $userId,
			'accountInf' => ! $accountInf ? '' : $accountInf
		];
	}

	public function get_counts ()
	{
		DB::DB()->query( 'DELETE FROM `' . DB::table( 'account_status' ) . '` WHERE (SELECT count(0) FROM `' . DB::table( 'accounts' ) . '` WHERE id=account_id)=0' );
		DB::DB()->query( 'DELETE FROM `' . DB::table( 'account_node_status' ) . '` WHERE (SELECT count(0) FROM `' . DB::table( 'account_nodes' ) . '` WHERE id=`' . DB::table( 'account_node_status' ) . '`.node_id)=0' );

		$accounts_list = DB::DB()->get_results( DB::DB()->prepare( "SELECT driver, COUNT(0) AS _count FROM " . DB::table( 'accounts' ) . " WHERE (user_id=%d OR is_public=1) AND blog_id=%d GROUP BY driver", [
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );
		$nodes_list    = DB::DB()->get_results( DB::DB()->prepare( 'SELECT driver, 1 AS _count FROM ' . DB::table( 'account_nodes' ) . ' WHERE account_id NOT IN ( SELECT id FROM ' . DB::table( 'accounts' ) . ' WHERE user_id = %d AND blog_id = %d ) AND is_public = 1 AND blog_id = %d GROUP BY driver', [
			get_current_user_id(),
			Helper::getBlogId(),
			Helper::getBlogId()
		] ), ARRAY_A );

		$fsp_accounts_count = [
			'total'     => 0,
			'fb'        => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'twitter'   => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'instagram' => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'linkedin'  => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'vk'        => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'pinterest' => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'reddit'    => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'tumblr'    => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'google_b'  => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'blogger'   => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'ok'        => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'plurk'     => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'telegram'  => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'medium'    => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			],
			'wordpress' => [
				'total'  => 0,
				'failed' => 0,
				'active' => 0
			]
		];

		foreach ( $accounts_list as $a_info )
		{
			if ( isset( $fsp_accounts_count[ $a_info[ 'driver' ] ] ) )
			{
				$fsp_accounts_count[ $a_info[ 'driver' ] ][ 'total' ] = $a_info[ '_count' ];
				$fsp_accounts_count[ 'total' ]                        += $a_info[ '_count' ];
			}
		}

		foreach ( $nodes_list as $node_info )
		{
			if ( isset( $fsp_accounts_count[ $node_info[ 'driver' ] ] ) )
			{
				$fsp_accounts_count[ $node_info[ 'driver' ] ][ 'total' ] += $node_info[ '_count' ];
				$fsp_accounts_count[ 'total' ]                           += $node_info[ '_count' ];
			}
		}

		$failed_accounts_list = DB::DB()->get_results( DB::DB()->prepare( "SELECT driver, COUNT(0) AS _count FROM " . DB::table( 'accounts' ) . " WHERE status = 'error' AND (user_id=%d OR is_public=1) AND blog_id=%d GROUP BY driver", [
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		foreach ( $failed_accounts_list as $a_info )
		{
			if ( isset( $fsp_accounts_count[ $a_info[ 'driver' ] ] ) )
			{
				$fsp_accounts_count[ $a_info[ 'driver' ] ][ 'failed' ] = $a_info[ '_count' ];
			}
		}

		$active_accounts = DB::DB()->get_results( DB::DB()->prepare( "SELECT `driver` FROM " . DB::table( 'accounts' ) . " WHERE ( `id` IN ( SELECT `account_id` FROM " . DB::table( 'account_status' ) . ") OR `id` IN ( SELECT `account_id` FROM " . DB::table( 'account_nodes' ) . " WHERE `id` IN ( SELECT `node_id` FROM " . DB::table( 'account_node_status' ) . " ) ) ) AND `user_id` = %d AND `blog_id` = %d GROUP BY `driver`", [
			get_current_user_id(),
			Helper::getBlogId()
		] ), ARRAY_A );

		foreach ( $active_accounts as $a_info )
		{
			if ( isset( $fsp_accounts_count[ $a_info[ 'driver' ] ] ) )
			{
				$fsp_accounts_count[ $a_info[ 'driver' ] ][ 'active' ] = 1;
			}
		}

		return $fsp_accounts_count;
	}

	public function get_groups ()
	{
		DB::DB()->query( 'DELETE FROM `' . DB::table( 'account_status' ) . '` WHERE (SELECT count(0) FROM `' . DB::table( 'accounts' ) . '` WHERE id=account_id)=0' );
		DB::DB()->query( 'DELETE FROM `' . DB::table( 'account_node_status' ) . '` WHERE (SELECT count(0) FROM `' . DB::table( 'account_nodes' ) . '` WHERE id=`' . DB::table( 'account_node_status' ) . '`.node_id)=0' );

		$accounts_table       = DB::table( 'accounts' );
		$account_status_table = DB::table( 'account_status' );
		$nodes_table          = DB::table( 'account_nodes' );
		$node_status_table    = DB::table( 'account_node_status' );
		$groups_table         = DB::table( 'account_groups' );
		$groups_data_table    = DB::table( 'account_groups_data' );

		$sql = "
			SELECT 
			       gt.*,
			       (SELECT COUNT(0) FROM `$groups_data_table` gdt WHERE gdt.group_id=gt.id) AS total,
			       (SELECT COUNT(0) FROM `$accounts_table` acct WHERE acct.id IN (SELECT gdt.node_id FROM `$groups_data_table` gdt WHERE gdt.group_id=gt.id AND gdt.node_type='account') AND acct.status='error') AS failed,
			       (SELECT COUNT(0) FROM `$account_status_table` ast WHERE ast.user_id=gt.user_id AND ast.account_id IN (SELECT gdt.node_id FROM `$groups_data_table` gdt WHERE gdt.group_id=gt.id AND gdt.node_type='account')) active_a,
			       (SELECT COUNT(0) FROM `$node_status_table` nst WHERE nst.user_id=gt.user_id AND nst.node_id IN (SELECT gdt.node_id FROM `$groups_data_table` gdt WHERE gdt.group_id=gt.id AND gdt.node_type='node')) active_n
			FROM `$groups_table` gt
			WHERE gt.user_id=%d AND gt.blog_id=%d 
			ORDER BY gt.name
		";

		$groups = DB::DB()->get_results( DB::DB()->prepare( $sql, [
			get_current_user_id(),
			Helper::getBlogId()
		] ), 'ARRAY_A' );

		if ( $groups )
		{
			return $groups;
		}
		else
		{
			return [];
		}
	}

	public function get_node_groups ()
	{
		$node_id           = Request::post( 'node_id', '', 'num' );
		$node_type         = Request::post( 'node_type', 'account', 'string', [ 'account', 'node' ] );
		$groups_table      = DB::table( 'account_groups' );
		$groups_data_table = DB::table( 'account_groups_data' );

		$groups = DB::DB()->get_results(
			DB::DB()->prepare(
				"
				SELECT gt.id, gt.name FROM `$groups_table` gt 
				WHERE gt.id IN (SELECT gdt.group_id FROM `$groups_data_table` gdt WHERE gdt.node_type=%s AND gdt.node_id=%d)
				 AND gt.blog_id=%d AND gt.user_id=%d
				", [
					$node_type,
					$node_id,
					Helper::getBlogId(),
					get_current_user_id()
				]
			),
			'ARRAY_A'
		);

		return [
			'id'        => $node_id,
			'node_type' => $node_type,
			'groups'    => isset( $groups ) ? $groups : []
		];
	}

	public static function get_group_nodes ( $group_id )
	{
		$accounts_table         = DB::table( 'accounts' );
		$account_status_table   = DB::table( 'account_status' );
		$grouped_accounts_table = DB::table( 'grouped_accounts' );
		$nodes_table            = DB::table( 'account_nodes' );
		$node_status_table      = DB::table( 'account_node_status' );
		$groups_table           = DB::table( 'account_groups' );
		$groups_data_table      = DB::table( 'account_groups_data' );

		$sql_accounts = "
			SELECT acct.*,
			       (SELECT ast.filter_type FROM `$account_status_table` ast WHERE ast.account_id=acct.id AND ast.user_id=%d) is_active,
				   (SELECT COUNT(0) FROM `$grouped_accounts_table` gat WHERE gat.account_id = acct.id AND gat.account_type = 'account' AND gat.user_id = %d) is_hidden
			FROM `$accounts_table` acct 
			INNER JOIN `$groups_data_table` gdt 
			    ON acct.id=gdt.node_id
			WHERE gdt.node_type='account' 
			  AND (acct.user_id=%d OR acct.is_public=1)
			  AND gdt.group_id IN (SELECT gt.id FROM `$groups_table` gt WHERE gt.id=%d AND gt.user_id=%d AND gt.blog_id=%d)
		";

		$accounts = DB::DB()->get_results( DB::DB()->prepare( $sql_accounts, [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			$group_id,
			get_current_user_id(),
			Helper::getBlogId()
		] ), 'ARRAY_A' );

		$sql_nodes = "
			SELECT nt.*,
			       (SELECT filter_type FROM `$node_status_table` nst WHERE nst.node_id=nt.id AND nst.user_id=%d) is_active,
				   (SELECT COUNT(0) FROM `$grouped_accounts_table` gat WHERE gat.account_id = nt.id AND gat.account_type = 'node' AND gat.user_id = %d) is_hidden
			FROM `$nodes_table` nt 
			INNER JOIN `$groups_data_table` gdt 
			    ON nt.id=gdt.node_id
			WHERE gdt.node_type='node' 
			  AND (nt.user_id=%d OR nt.is_public=1)
			  AND gdt.group_id IN (SELECT gt.id FROM `$groups_table` gt WHERE gt.id=%d AND gt.user_id=%d AND gt.blog_id=%d)
			ORDER BY nt.account_id, (CASE nt.node_type WHEN 'ownpage' THEN 1 WHEN 'group' THEN 2 WHEN 'page' THEN 3 END), nt.name
		";

		$nodes = DB::DB()->get_results( DB::DB()->prepare( $sql_nodes, [
			get_current_user_id(),
			get_current_user_id(),
			get_current_user_id(),
			$group_id,
			get_current_user_id(),
			Helper::getBlogId()
		] ), 'ARRAY_A' );

		$sn_names = [
			'fb'        => fsp__( 'FB' ),
			'twitter'   => fsp__( 'Twitter' ),
			'instagram' => fsp__( 'Instagram' ),
			'linkedin'  => fsp__( 'Linkedin' ),
			'vk'        => fsp__( 'VK' ),
			'pinterest' => fsp__( 'Pinterest' ),
			'reddit'    => fsp__( 'Reddit' ),
			'tumblr'    => fsp__( 'Tumblr' ),
			'ok'        => fsp__( 'OK' ),
			'plurk'     => fsp__( 'Plurk' ),
			'google_b'  => fsp__( 'GMB' ),
			'blogger'   => fsp__( 'Blogger' ),
			'telegram'  => fsp__( 'Telegram' ),
			'medium'    => fsp__( 'Medium' ),
			'wordpress' => fsp__( 'WordPress' )
		];

		$i           = 0;
		$nodes_count = count( $nodes );

		$public_communities = [];

		foreach ( $accounts as &$account )
		{
			$account[ 'sn_name' ] = $sn_names[ $account[ 'driver' ] ];

			while ( $i < $nodes_count )
			{
				$nodes[ $i ][ 'sn_name' ] = $sn_names[ $nodes[ $i ][ 'driver' ] ];

				if ( $nodes[ $i ][ 'account_id' ] === $account[ 'id' ] )
				{
					$account[ 'node_list' ][] = $nodes[ $i ];
					$i++;
				}
				else if ( $nodes[ $i ][ 'account_id' ] < $account[ 'id' ] )
				{
					$public_communities[] = $nodes[ $i ];
					$i++;
				}
				else
				{
					break;
				}
			}
		}

		while ( $i < $nodes_count )
		{
			$nodes[ $i ][ 'sn_name' ] = $sn_names[ $nodes[ $i ][ 'driver' ] ];
			$public_communities[]     = $nodes[ $i ];
			$i++;
		}

		return [
			'accounts_list'      => $accounts,
			'public_communities' => $public_communities,
			'count'              => count( $accounts ) + $nodes_count
		];
	}

	public static function bulk_action_public ( $ids )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::public_private_account( $account_id, 1 );
		}

		foreach ( $node_ids as $node_id )
		{
			self::public_private_node( $node_id, 1 );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_private ( $ids )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::public_private_account( $account_id, 0 );
		}

		foreach ( $node_ids as $node_id )
		{
			self::public_private_node( $node_id, 0 );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_activate ( $ids, $for_all = FALSE )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::activate_deactivate_account( $account_id, 1, 'no', NULL, $for_all );
		}

		foreach ( $node_ids as $node_id )
		{
			self::activate_deactivate_node( $node_id, 1, 'no', NULL, $for_all );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_activate_all ( $ids )
	{
		self::bulk_action_public( $ids );

		return $this->bulk_action_activate( $ids, TRUE );
	}

	public static function bulk_action_activate_condition ( $ids, $filter_type, $categories_arr, $for_all = FALSE )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::activate_deactivate_account( $account_id, 1, $filter_type, $categories_arr, $for_all );
		}

		foreach ( $node_ids as $node_id )
		{
			self::activate_deactivate_node( $node_id, 1, $filter_type, $categories_arr, $for_all );
		}

		if ( $for_all )
		{
			self::bulk_action_public( $ids );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_deactivate ( $ids, $for_all = FALSE )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::activate_deactivate_account( $account_id, 0, 'no', NULL, $for_all );
		}

		foreach ( $node_ids as $node_id )
		{
			self::activate_deactivate_node( $node_id, 0, 'no', NULL, $for_all );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_deactivate_all ( $ids )
	{
		$this->bulk_action_private( $ids );

		return $this->bulk_action_deactivate( $ids, TRUE );
	}

	public function bulk_action_delete ( $ids )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::delete_account( $account_id );
		}

		foreach ( $node_ids as $node_id )
		{
			self::delete_node( $node_id );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_hide ( $ids )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::hide_unhide_account( $account_id, 1 );
		}

		foreach ( $node_ids as $node_id )
		{
			self::hide_unhide_node( $node_id, 1 );
		}

		return [ 'status' => TRUE ];
	}

	public function bulk_action_unhide ( $ids )
	{
		$account_ids = [];
		$node_ids    = [];

		foreach ( $ids as $id )
		{
			if ( $id[ 'type' ] === 'account' )
			{
				$account_ids[] = $id[ 'id' ];
			}
			else
			{
				$node_ids[] = $id[ 'id' ];
			}
		}

		foreach ( $account_ids as $account_id )
		{
			self::hide_unhide_account( $account_id, 0 );
		}

		foreach ( $node_ids as $node_id )
		{
			self::hide_unhide_node( $node_id, 0 );
		}

		return [ 'status' => TRUE ];
	}

	public static function delete_account ( $account_id )
	{
		$check_account = DB::fetch( 'accounts', $account_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}
		else if ( $check_account[ 'user_id' ] != get_current_user_id() )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'You don\'t have permission to remove the account! Only the account owner can remove it.' )
			];
		}

		DB::DB()->delete( DB::table( 'accounts' ), [ 'id' => $account_id ] );
		DB::DB()->delete( DB::table( 'account_status' ), [ 'account_id' => $account_id ] );
		DB::DB()->delete( DB::table( 'account_access_tokens' ), [ 'account_id' => $account_id ] );

		DB::DB()->query( 'DELETE FROM ' . DB::table( 'account_node_status' ) . ' WHERE node_id IN ( SELECT id FROM ' . DB::table( 'account_nodes' ) . ' WHERE account_id = "' . $account_id . '")' );

		DB::DB()->delete( DB::table( 'account_nodes' ), [ 'account_id' => $account_id ] );

		DB::DB()->delete( DB::table( 'account_groups_data' ), [ 'node_type' => 'account', 'node_id' => $account_id ] );

		Helper::deleteCustomSettings( 'account', $account_id );

		if ( $check_account[ 'driver' ] === 'instagram' )
		{
			$checkIfUsernameExist = DB::fetch( 'accounts', [
				'blog_id'  => Helper::getBlogId(),
				'username' => $check_account[ 'username' ],
				'driver'   => $check_account[ 'driver' ]
			] );

			if ( ! $checkIfUsernameExist )
			{
				DB::DB()->delete( DB::table( 'account_sessions' ), [
					'driver'   => $check_account[ 'driver' ],
					'username' => $check_account[ 'username' ]
				] );
			}
		}
		else if ( $check_account[ 'driver' ] === 'pinterest' )
		{
			DB::DB()->delete( DB::table( 'account_sessions' ), [
				'driver'   => 'pinterest',
				'username' => $check_account[ 'username' ]
			] );
		}

		return [ 'status' => TRUE ];
	}

	public static function delete_node ( $node_id )
	{
		$check_account = DB::fetch( 'account_nodes', $node_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_account[ 'user_id' ] != get_current_user_id() )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'You don\'t have permission to remove the account! Only the account owner can remove it.' )
			];
		}

		DB::DB()->delete( DB::table( 'account_nodes' ), [ 'id' => $node_id ] );
		DB::DB()->delete( DB::table( 'account_node_status' ), [ 'node_id' => $node_id ] );
		DB::DB()->delete( DB::table( 'account_groups_data' ), [ 'node_type' => 'node', 'node_id' => $node_id ] );

		Helper::deleteCustomSettings( 'node', $node_id );

		return [ 'status' => TRUE ];
	}

	public static function public_private_account ( $account_id, $checked )
	{
		$check_account = DB::fetch( 'accounts', $account_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_account[ 'user_id' ] != get_current_user_id() )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'Only the account owner can make it public or private!' )
			];
		}

		if ( $check_account[ 'status' ] === 'error' && $checked )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'Failed accounts can\'t be public!' ) ];
		}

		DB::DB()->update( DB::table( 'accounts' ), [
			'is_public' => $checked
		], [
			'id'      => $account_id,
			'user_id' => get_current_user_id()
		] );

		return [ 'status' => TRUE ];
	}

	public static function public_private_node ( $node_id, $checked )
	{
		$check_node = DB::fetch( 'account_nodes', $node_id );

		if ( ! $check_node )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_node[ 'user_id' ] != get_current_user_id() )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'Only the account owner can make it public or private!' )
			];
		}

		DB::DB()->update( DB::table( 'account_nodes' ), [ 'is_public' => $checked ], [ 'id' => $node_id ] );

		return [ 'status' => TRUE ];
	}

	public static function activate_deactivate_account ( $account_id, $checked, $filter_type = 'no', $categories_arr = NULL, $for_all = FALSE )
	{
		$check_account = DB::fetch( 'accounts', $account_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_account[ 'user_id' ] != get_current_user_id() && $check_account[ 'is_public' ] != 1 )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'You haven\'t sufficient permissions!' )
			];
		}

		if ( $checked )
		{
			if ( $check_account[ 'status' ] === 'error' )
			{
				return [ 'status' => FALSE, 'error_msg' => fsp__( 'Failed accounts can\'t be activated!' ) ];
			}

			if ( $for_all )
			{
				DB::DB()->delete( DB::table( 'account_status' ), [ 'account_id' => $account_id ] );

				$offset = 0;
				$number = 400;
				while ( $users = get_users( [
					'role__not_in' => explode( '|', Helper::getOption( 'hide_menu_for', '' ) ),
					'fields'       => 'ID',
					'offset'       => $offset,
					'number'       => $number
				] ) )
				{
					$rows = [];

					while ( $uid = array_splice( $users, 0, 1 ) )
					{
						$rows[] = [
							'account_id'  => $account_id,
							'user_id'     => $uid[ 0 ],
							'filter_type' => $filter_type,
							'categories'  => $categories_arr
						];
					}

					DB::insertAll( 'account_status', [ 'account_id', 'user_id', 'filter_type', 'categories' ], $rows );

					$offset += $number;
				}
			}
			else
			{
				$check_is_active = DB::fetch( 'account_status', [
					'account_id' => $account_id,
					'user_id'    => get_current_user_id(),
				] );

				if ( ! $check_is_active )
				{
					DB::DB()->insert( DB::table( 'account_status' ), [
						'account_id'  => $account_id,
						'user_id'     => get_current_user_id(),
						'filter_type' => $filter_type,
						'categories'  => $categories_arr
					] );
				}
				else
				{
					DB::DB()->update( DB::table( 'account_status' ), [
						'filter_type' => $filter_type,
						'categories'  => $categories_arr
					], [ 'id' => $check_is_active[ 'id' ] ] );
				}
			}
		}
		else
		{
			if ( $for_all )
			{
				$sql = [
					'account_id' => $account_id
				];
			}
			else
			{
				$sql = [
					'account_id' => $account_id,
					'user_id'    => get_current_user_id()
				];
			}

			DB::DB()->delete( DB::table( 'account_status' ), $sql );
		}

		return [ 'status' => TRUE ];
	}

	public static function activate_deactivate_node ( $node_id, $checked, $filter_type = 'no', $categories_arr = NULL, $for_all = FALSE )
	{
		$check_account = DB::fetch( 'account_nodes', $node_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_account[ 'user_id' ] != get_current_user_id() && $check_account[ 'is_public' ] != 1 )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'You haven\'t sufficient permissions!' )
			];
		}

		if ( $checked )
		{
			$check_account_parent = DB::fetch( 'accounts', $check_account[ 'account_id' ] );

			if ( $check_account_parent[ 'status' ] === 'error' )
			{
				return [
					'status'    => FALSE,
					'error_msg' => fsp__( 'Failed accounts and their communities can\'t be activated!' )
				];
			}

			if ( $for_all )
			{
				DB::DB()->delete( DB::table( 'account_node_status' ), [ 'node_id' => $node_id ] );

				$offset = 0;
				$number = 400;
				while ( $users = get_users( [
					'role__not_in' => explode( '|', Helper::getOption( 'hide_menu_for', '' ) ),
					'fields'       => 'ID',
					'offset'       => $offset,
					'number'       => $number
				] ) )
				{
					$rows = [];

					while ( $uid = array_splice( $users, 0, 1 ) )
					{
						$rows[] = [
							'node_id'     => $node_id,
							'user_id'     => $uid[ 0 ],
							'filter_type' => $filter_type,
							'categories'  => $categories_arr
						];
					}

					DB::insertAll( 'account_node_status', [
						'node_id',
						'user_id',
						'filter_type',
						'categories'
					], $rows );

					$offset += $number;
				}
			}
			else
			{
				$check_is_active = DB::fetch( 'account_node_status', [
					'node_id' => $node_id,
					'user_id' => get_current_user_id()
				] );

				if ( ! $check_is_active )
				{
					DB::DB()->insert( DB::table( 'account_node_status' ), [
						'node_id'     => $node_id,
						'user_id'     => get_current_user_id(),
						'filter_type' => $filter_type,
						'categories'  => $categories_arr
					] );
				}
				else
				{
					DB::DB()->update( DB::table( 'account_node_status' ), [
						'filter_type' => $filter_type,
						'categories'  => $categories_arr
					], [ 'id' => $check_is_active[ 'id' ] ] );
				}
			}
		}
		else
		{
			if ( $for_all )
			{
				$sql = [
					'node_id' => $node_id
				];
			}
			else
			{
				$sql = [
					'node_id' => $node_id,
					'user_id' => get_current_user_id()
				];
			}

			DB::DB()->delete( DB::table( 'account_node_status' ), $sql );
		}

		return [ 'status' => TRUE ];
	}

	public static function hide_unhide_account ( $account_id, $checked )
	{
		$check_account = DB::fetch( 'accounts', $account_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_account[ 'user_id' ] != get_current_user_id() && $check_account[ 'is_public' ] != 1 )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'You haven\'t sufficient permissions!' )
			];
		}

		$get_visibility = DB::fetch( 'grouped_accounts', [
			'account_id'   => $account_id,
			'account_type' => 'account',
			'user_id'      => get_current_user_id()
		] );

		if ( ! $get_visibility && $checked )
		{
			DB::DB()->insert( DB::table( 'grouped_accounts' ), [
				'account_id'   => $account_id,
				'account_type' => 'account',
				'user_id'      => get_current_user_id()
			] );
		}
		else if ( $get_visibility && ! $checked )
		{
			DB::DB()->delete( DB::table( 'grouped_accounts' ), [
				'account_id'   => $account_id,
				'account_type' => 'account',
				'user_id'      => get_current_user_id()
			] );
		}

		return [ 'status' => TRUE ];
	}

	public static function hide_unhide_node ( $node_id, $checked )
	{
		$check_account = DB::fetch( 'account_nodes', $node_id );

		if ( ! $check_account )
		{
			return [ 'status' => FALSE, 'error_msg' => fsp__( 'The account isn\'t found!' ) ];
		}

		if ( $check_account[ 'user_id' ] != get_current_user_id() && $check_account[ 'is_public' ] != 1 )
		{
			return [
				'status'    => FALSE,
				'error_msg' => fsp__( 'You haven\'t sufficient permissions!' )
			];
		}

		$get_visibility = DB::fetch( 'grouped_accounts', [
			'account_id'   => $node_id,
			'account_type' => 'node',
			'user_id'      => get_current_user_id()
		] );

		if ( ! $get_visibility && $checked )
		{
			DB::DB()->insert( DB::table( 'grouped_accounts' ), [
				'account_id'   => $node_id,
				'account_type' => 'node',
				'user_id'      => get_current_user_id()
			] );
		}
		else if ( $get_visibility && ! $checked )
		{
			DB::DB()->delete( DB::table( 'grouped_accounts' ), [
				'account_id'   => $node_id,
				'account_type' => 'node',
				'user_id'      => get_current_user_id()
			] );
		}

		return [ 'status' => TRUE ];
	}
}
