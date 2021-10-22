<?php

namespace FSPoster\App\Libraries\pinterest;

use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Curl;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\Request;
use FSPoster\App\Providers\Session;
use FSPoster\App\Providers\SocialNetwork;

class Pinterest extends SocialNetwork
{
	/**
	 * @param array $account_info
	 * @param string $type
	 * @param string $message
	 * @param string $link
	 * @param array $images
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function sendPost ( $boardId, $type, $message, $link, $images, $accessToken, $proxy )
	{
		$sendData = [
			'board' => $boardId,
			'note'  => $message,
			'link'  => $link
		];

		if ( $type === 'image' )
		{
			$sendData[ 'image_url' ] = reset( $images );
		}
		else
		{
			return [
				'status'    => 'error',
				'error_msg' => 'An image is required to pin on board!'
			];
		}

		$result = self::cmd( 'pins', 'POST', $accessToken, $sendData, $proxy );

		if ( isset( $result[ 'error' ] ) && isset( $result[ 'error' ][ 'message' ] ) )
		{
			$result2 = [
				'status'    => 'error',
				'error_msg' => $result[ 'error' ][ 'message' ]
			];
		}
		else if ( isset( $result[ 'message' ] ) )
		{
			$result2 = [
				'status'    => 'error',
				'error_msg' => $result[ 'message' ]
			];
		}
		else
		{
			$result2 = [
				'status' => 'ok',
				'id'     => $result[ 'data' ][ 'id' ]
			];
		}

		return $result2;
	}

	/**
	 * @param string $cmd
	 * @param string $method
	 * @param string $accessToken
	 * @param array $data
	 * @param string $proxy
	 *
	 * @return array|mixed|object
	 */
	public static function cmd ( $cmd, $method, $accessToken, array $data = [], $proxy = '' )
	{
		$data[ 'access_token' ] = $accessToken;

		$url = 'https://api.pinterest.com/v1/' . trim( $cmd, '/' ) . '/';

		$method = $method === 'POST' ? 'POST' : ( $method === 'DELETE' ? 'DELETE' : 'GET' );

		$data1 = Curl::getContents( $url, $method, $data, [], $proxy );
		$data  = json_decode( $data1, TRUE );

		if ( ! is_array( $data ) )
		{
			$data = [ 'message' => 'Error data!' ];
		}

		return $data;
	}

	/**
	 * @param integer $appId
	 *
	 * @return string
	 */
	public static function getLoginURL ( $appId )
	{
		Session::set( 'app_id', $appId );
		Session::set( 'proxy', Request::get( 'proxy', '', 'string' ) );

		$appInf = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'pinterest' ] );
		if ( ! $appInf )
		{
			self::error( fsp__( 'Error! The App isn\'t found!' ) );
		}

		$appId = urlencode( $appInf[ 'app_id' ] );

		$callbackUrl = urlencode( self::callbackUrl() );

		return "https://api.pinterest.com/oauth/?response_type=code&redirect_uri=" . $callbackUrl . "&client_id=" . $appId . "&scope=read_public,write_public";
	}

	/**
	 * @return string
	 */
	public static function callbackURL ()
	{
		return site_url() . '/?pinterest_callback=1';
	}

	/**
	 * @return bool
	 */
	public static function getAccessToken ()
	{
		$appId = (int) Session::get( 'app_id' );

		if ( empty( $appId ) )
		{
			return FALSE;
		}

		$code = Request::get( 'code', '', 'string' );

		if ( empty( $code ) )
		{
			$error_message = Request::get( 'error_message', '', 'str' );

			self::error( $error_message );
		}

		$proxy = Session::get( 'proxy' );

		Session::remove( 'app_id' );
		Session::remove( 'proxy' );

		$appInf    = DB::fetch( 'apps', [ 'id' => $appId, 'driver' => 'pinterest' ] );
		$appSecret = urlencode( $appInf[ 'app_secret' ] );
		$appId2    = urlencode( $appInf[ 'app_id' ] );

		$token_url = "https://api.pinterest.com/v1/oauth/token?grant_type=authorization_code&client_id={$appId2}&client_secret={$appSecret}&code={$code}";

		$response = Curl::getContents( $token_url, 'POST', [], [], $proxy );
		$params   = json_decode( $response, TRUE );

		if ( isset( $params[ 'message' ] ) )
		{
			self::error( $params[ 'message' ] );
		}

		$access_token = esc_html( $params[ 'access_token' ] );

		self::authorize( $appId, $access_token, $proxy );
	}

	/**
	 * @param integer $appId
	 * @param string $accessToken
	 * @param string $proxy
	 */
	public static function authorize ( $appId, $accessToken, $proxy )
	{
		$me = self::cmd( 'me', 'GET', $accessToken, [ 'fields' => 'id,username,image,first_name,last_name,counts' ], $proxy );

		if ( isset( $me[ 'message' ] ) && is_string( $me[ 'message' ] ) )
		{
			self::error( $me[ 'message' ] );
		}

		if ( ! isset( $me[ 'data' ] ) )
		{
			self::error();
		}

		$me   = $me[ 'data' ];
		$meId = $me[ 'id' ];

		if ( ! get_current_user_id() > 0 )
		{
			Helper::response( FALSE, fsp__( 'The current WordPress user ID is not available. Please, check if your security plugins prevent user authorization.' ) );
		}

		$checkLoginRegistered = DB::fetch( 'accounts', [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'driver'     => 'pinterest',
			'profile_id' => $meId
		] );

		$dataSQL = [
			'blog_id'     => Helper::getBlogId(),
			'user_id'     => get_current_user_id(),
			'name'        => $me[ 'first_name' ] . ' ' . $me[ 'last_name' ],
			'driver'      => 'pinterest',
			'profile_id'  => $meId,
			'profile_pic' => $me[ 'image' ][ '60x60' ][ 'url' ],
			'username'    => $me[ 'username' ],
			'proxy'       => $proxy
		];

		if ( ! $checkLoginRegistered )
		{
			DB::DB()->insert( DB::table( 'accounts' ), $dataSQL );

			$accId = DB::DB()->insert_id;
		}
		else
		{
			$accId = $checkLoginRegistered[ 'id' ];

			DB::DB()->update( DB::table( 'accounts' ), $dataSQL, [ 'id' => $accId ] );

			DB::DB()->delete( DB::table( 'account_access_tokens' ), [ 'account_id' => $accId, 'app_id' => $appId ] );
		}

		// acccess token
		DB::DB()->insert( DB::table( 'account_access_tokens' ), [
			'account_id'   => $accId,
			'app_id'       => $appId,
			'access_token' => $accessToken
		] );

		// set default board
		self::refetch_account( $accId, $accessToken, $proxy );

		self::closeWindow();
	}

	/**
	 * @param integer $post_id
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function getStats ( $post_id, $accessToken, $proxy )
	{
		$result = self::cmd( 'pins/' . $post_id, 'GET', $accessToken, [ 'fields' => 'counts' ], $proxy );

		return [
			'comments' => isset( $result[ 'data' ][ 'counts' ][ 'comments' ] ) ? $result[ 'data' ][ 'counts' ][ 'comments' ] : 0,
			'like'     => isset( $result[ 'data' ][ 'counts' ][ 'saves' ] ) ? $result[ 'data' ][ 'counts' ][ 'saves' ] : 0,
			'shares'   => 0,
			'details'  => 'Saves: ' . ( isset( $result[ 'data' ][ 'counts' ][ 'saves' ] ) ? $result[ 'data' ][ 'counts' ][ 'saves' ] : 0 )
		];
	}

	/**
	 * @param string $accessToken
	 * @param string $proxy
	 *
	 * @return array
	 */
	public static function checkAccount ( $accessToken, $proxy )
	{
		$result = [
			'error'     => TRUE,
			'error_msg' => NULL
		];

		$me = self::cmd( 'me', 'GET', $accessToken, [ 'fields' => 'id,username,image,first_name,last_name,counts' ], $proxy );

		if ( isset( $me[ 'message' ] ) && is_string( $me[ 'message' ] ) )
		{
			$result[ 'error_msg' ] = $me[ 'message' ];
		}
		else if ( isset( $me[ 'data' ] ) )
		{
			$result[ 'error' ] = FALSE;
		}

		return $result;
	}

	public static function refetch_account ( $account_id, $access_token, $proxy )
	{
		$boards    = self::cmd( 'me/boards', 'GET', $access_token, [ 'fields' => 'id,name,url,image' ], $proxy );
		$get_nodes = DB::DB()->get_results( DB::DB()->prepare( 'SELECT id, node_id FROM ' . DB::table( 'account_nodes' ) . ' WHERE account_id = %d', [ $account_id ] ), ARRAY_A );
		$my_nodes  = [];

		foreach ( $get_nodes as $node )
		{
			$my_nodes[ $node[ 'id' ] ] = $node[ 'node_id' ];
		}

		if ( isset( $boards[ 'data' ] ) && is_array( $boards[ 'data' ] ) )
		{
			foreach ( $boards[ 'data' ] as $board )
			{
				$board_id   = $board[ 'id' ];
				$boardName  = $board[ 'name' ];
				$screenName = str_replace( 'https://www.pinterest.com/', '', $board[ 'url' ] );
				$image      = $board[ 'image' ];

				$image = reset( $image );
				$image = isset( $image[ 'url' ] ) ? $image[ 'url' ] : '';

				if ( ! in_array( $board_id, $my_nodes ) )
				{
					DB::DB()->insert( DB::table( 'account_nodes' ), [
						'blog_id'     => Helper::getBlogId(),
						'user_id'     => get_current_user_id(),
						'driver'      => 'pinterest',
						'account_id'  => $account_id,
						'node_type'   => 'board',
						'node_id'     => $board_id,
						'name'        => $boardName,
						'cover'       => $image,
						'screen_name' => $screenName
					] );
				}
				else
				{
					DB::DB()->update( DB::table( 'account_nodes' ), [
						'name'        => $boardName,
						'cover'       => $image,
						'screen_name' => $screenName
					], [
						'account_id' => $account_id,
						'node_id'    => $board_id
					] );
				}

				unset( $my_nodes[ array_search( $board_id, $my_nodes ) ] );
			}
		}

		if ( ! empty( $my_nodes ) )
		{
			DB::DB()->query( 'DELETE FROM ' . DB::table( 'account_nodes' ) . ' WHERE id IN (' . implode( ',', array_keys( $my_nodes ) ) . ')' );
			DB::DB()->query( 'DELETE FROM ' . DB::table( 'account_node_status' ) . ' WHERE node_id IN (' . implode( ',', array_keys( $my_nodes ) ) . ')' );
		}

		return [ 'status' => TRUE ];
	}
}