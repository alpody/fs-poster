<?php

namespace FSPoster\App\Libraries\instagram;

use Exception;
use InvalidArgumentException;
use FSPoster\App\Providers\DB;
use FSPoster\App\Providers\Date;
use FSPoster\App\Providers\Helper;
use FSPoster\App\Providers\AccountService;
use FSPoster\App\Libraries\PHPImage\PHPImage;
use FSPoster\App\Providers\PersianStringDecorator;

class InstagramApi
{
	const MIN_ASPECT_RATIO = 0.9;

	const MAX_ASPECT_RATIO = 1.91;

	private static $recycle_bin = [];

	/**
	 * @param array $accountInfo
	 * @param string $type
	 * @param string $message
	 * @param string $link
	 * @param $images
	 * @param $video
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function sendPost ( $accountInfo, $type, $message, $link, $images, $video )
	{
		if ( $type == 'image' )
		{
			if ( ! extension_loaded( 'gd' ) )
			{
				return [
					'status'    => 'error',
					'error_msg' => fsp__( 'You should install and enable GD, libjpeg, libpng, and freetype libraries. <a href=\'https://www.fs-poster.com/documentation/commonly-encountered-issues#issue3\' target=\'_blank\'>Learn more.</a>', [], FALSE )
				];
			}

			try
			{
				$photo = self::imageForFeed( $images[ 0 ] );
			}
			catch ( Exception $e )
			{
				return [
					'status'    => 'error',
					'error_msg' => InstagramApi::error( $e->getMessage(), $accountInfo[ 'id' ] )
				];
			}
		}
		else
		{
			try
			{
				$video = self::renderVideo( $video, 'timeline' );
			}
			catch ( Exception $e )
			{
				return [
					'status'    => 'error',
					'error_msg' => InstagramApi::error( $e->getMessage() )
				];
			}
		}

		if ( self::isCookieMethod( $accountInfo ) )
		{
			$apiInstance = new InstagramCookieMethod( self::getCookies( $accountInfo ), $accountInfo[ 'proxy' ] );
		}
		else
		{
			$accountInfo[ 'password' ] = substr( $accountInfo[ 'password' ], 0, 9 ) === '(-F-S-P-)' ? explode( '(-F-S-P-)', base64_decode( str_rot13( substr( $accountInfo[ 'password' ], 9 ) ) ) )[ 0 ] : $accountInfo[ 'password' ];

			$apiInstance = new InstagramLoginPassMethod( $accountInfo[ 'username' ], $accountInfo[ 'password' ], $accountInfo[ 'proxy' ] );
		}

		if ( Helper::getOption( 'instagram_autocut_text', '1' ) == 1 && mb_strlen( $message ) > 2200 )
		{
			$message = mb_substr( $message, 0, 2197 ) . '...';
		}

		if ( $type == 'image' )
		{
			try
			{
				return $apiInstance->uploadPhoto( $accountInfo[ 'id' ], $photo, $message, $link, 'timeline' );
			}
			catch ( Exception $e )
			{
				return [
					'status'    => 'error',
					'error_msg' => InstagramApi::error( $e->getMessage(), $accountInfo[ 'id' ] )
				];
			}
		}
		else
		{
			try
			{
				return $apiInstance->uploadVideo( $accountInfo[ 'id' ], $video, $message, $link, 'timeline' );
			}
			catch ( Exception $e )
			{
				return [
					'status'    => 'error',
					'error_msg' => InstagramApi::error( $e->getMessage(), $accountInfo[ 'id' ] )
				];
			}
		}
	}

	/**
	 * @param array $accountInfo
	 * @param string $type
	 * @param string $message
	 * @param string $link
	 * @param $images
	 * @param $video
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function sendStory ( $accountInfo, $type, $message, $link, $images, $video )
	{
		if ( $type == 'image' )
		{
			$photo = self::imageForStory( $images[ 0 ], $message );
		}
		else
		{
			$video = self::renderVideo( $video, 'story' );
		}

		if ( self::isCookieMethod( $accountInfo ) )
		{
			if ( $type == 'video' )
			{
				throw new Exception( fsp__( 'The cookie method doesn\'t support sharing videos on stories!' ) );
			}

			$apiInstance = new InstagramCookieMethod( self::getCookies( $accountInfo ), $accountInfo[ 'proxy' ] );
		}
		else
		{
			$accountInfo[ 'password' ] = substr( $accountInfo[ 'password' ], 0, 9 ) === '(-F-S-P-)' ? explode( '(-F-S-P-)', base64_decode( str_rot13( substr( $accountInfo[ 'password' ], 9 ) ) ) )[ 0 ] : $accountInfo[ 'password' ];

			$apiInstance = new InstagramLoginPassMethod( $accountInfo[ 'username' ], $accountInfo[ 'password' ], $accountInfo[ 'proxy' ] );
		}

		if ( $type == 'image' )
		{
			try
			{
				return $apiInstance->uploadPhoto( $accountInfo[ 'id' ], $photo, $message, $link, 'story' );
			}
			catch ( Exception $e )
			{
				return [
					'status'    => 'error',
					'error_msg' => InstagramApi::error( $e->getMessage(), $accountInfo[ 'id' ] )
				];
			}
		}
		else
		{
			try
			{
				return $apiInstance->uploadVideo( $accountInfo[ 'id' ], $video, $message, $link, 'story' );
			}
			catch ( Exception $e )
			{
				return [
					'status'    => 'error',
					'error_msg' => InstagramApi::error( $e->getMessage(), $accountInfo[ 'id' ] )
				];
			}
		}
	}

	/**
	 * @param int $postId
	 * @param array $accountInfo
	 *
	 * @return array
	 */
	public static function getStats ( $postId, $postId2, $accountInfo )
	{
		$emptyData = [
			'comments' => 0,
			'like'     => 0,
			'shares'   => 0,
			'details'  => ''
		];

		if ( empty( $accountInfo ) || empty( $accountInfo[ 'username' ] ) || empty( $accountInfo[ 'password' ] ) )
		{
			return $emptyData;
		}

		if ( self::isCookieMethod( $accountInfo ) )
		{
			$get_cookies = self::getCookies( $accountInfo );

			if ( empty( $get_cookies ) )
			{
				return $emptyData;
			}

			$api           = new InstagramCookieMethod( $get_cookies, $accountInfo[ 'proxy' ] );
			$commentsLikes = $api->getPostInfo( $postId2 );

			return [
				'comments' => $commentsLikes[ 'comments' ],
				'like'     => $commentsLikes[ 'likes' ],
				'shares'   => 0,
				'details'  => ''
			];
		}
		else
		{
			$accountInfo[ 'password' ] = substr( $accountInfo[ 'password' ], 0, 9 ) === '(-F-S-P-)' ? explode( '(-F-S-P-)', base64_decode( str_rot13( substr( $accountInfo[ 'password' ], 9 ) ) ) )[ 0 ] : $accountInfo[ 'password' ];

			$instagram = new InstagramLoginPassMethod( $accountInfo[ 'username' ], $accountInfo[ 'password' ], $accountInfo[ 'proxy' ] );
			$info      = $instagram->getMediaInfo( $postId );

			return [
				'comments' => isset( $info[ 'items' ][ 0 ][ 'comment_count' ] ) ? (int) $info[ 'items' ][ 0 ][ 'comment_count' ] : 0,
				'like'     => isset( $info[ 'items' ][ 0 ][ 'like_count' ] ) ? (int) $info[ 'items' ][ 0 ][ 'like_count' ] : 0,
				'shares'   => 0,
				'details'  => ''
			];
		}
	}

	/**
	 * @param array $accountInfo
	 *
	 * @return array
	 */
	public static function checkAccount ( $accountInfo )
	{
		$result = [
			'error'     => FALSE,
			'error_msg' => NULL
		];

		if ( self::isCookieMethod( $accountInfo ) )
		{
			$instagram     = new InstagramCookieMethod( self::getCookies( $accountInfo ), $accountInfo[ 'proxy' ] );
			$instagram_res = $instagram->profileInfo();

			if ( ! $instagram_res )
			{
				$result = [
					'error'     => TRUE,
					'error_msg' => fsp__( 'The account is disconnected from the FS Poster plugin. Please update your account cookie to connect it to the plugin again. <a href=\'https://www.fs-poster.com/documentation/commonly-encountered-issues#issue8\' target=\'_blank\'>How to?</a>', [], FALSE )
				];
			}
		}
		else
		{
			$accountInfo[ 'password' ] = substr( $accountInfo[ 'password' ], 0, 9 ) === '(-F-S-P-)' ? explode( '(-F-S-P-)', base64_decode( str_rot13( substr( $accountInfo[ 'password' ], 9 ) ) ) )[ 0 ] : $accountInfo[ 'password' ];

			$instagram     = new InstagramLoginPassMethod( $accountInfo[ 'username' ], $accountInfo[ 'password' ], $accountInfo[ 'proxy' ] );
			$instagram_res = $instagram->login();

			if ( isset( $instagram_res[ 'status' ] ) && $instagram_res[ 'status' ] === 'fail' )
			{
				$result = [
					'error'     => TRUE,
					'error_msg' => InstagramApi::error( $instagram_res[ 'message' ], $accountInfo[ 'id' ] )
				];
			}
		}

		return $result;
	}

	public static function handleResponse ( $result, $username, $password, $proxy )
	{
		if ( $result[ 'status' ] == 'fail' && isset( $result[ 'two_factor_required' ] ) && isset( $result[ 'two_factor_info' ] ) )
		{
			$phone_number          = $result[ 'two_factor_info' ][ 'obfuscated_phone_number' ];
			$two_factor_identifier = $result[ 'two_factor_info' ][ 'two_factor_identifier' ];

			Helper::response( TRUE, [
				'do'                    => 'two_factor',
				'message'               => fsp__( 'Enter the verification code we have sent to your number ending in %s', [ esc_html( $phone_number ) ] ),
				'two_factor_identifier' => $two_factor_identifier
			] );
		}
		else if ( $result[ 'status' ] == 'ok' && isset( $result[ 'step_name' ] ) && strpos( $result[ 'step_name' ], 'verify' ) === 0 && isset( $result[ 'nonce_code' ] ) )
		{
			$contact_point = $result[ 'step_data' ][ 'contact_point' ];
			$account_id    = $result[ 'user_id' ];
			$nonce_code    = $result[ 'nonce_code' ];

			Helper::response( TRUE, [
				'do'         => 'challenge',
				'message'    => fsp__( 'Please enter the verification code we have sent to %s', [ esc_html( $contact_point ) ] ),
				'user_id'    => $account_id,
				'nonce_code' => $nonce_code
			] );
		}
		else if ( ! ( $result[ 'status' ] == 'ok' && ! empty( $result[ 'logged_in_user' ] ) ) )
		{
			if ( isset( $result[ 'message' ] ) && is_string( $result[ 'message' ] ) )
			{
				$error_msg = InstagramApi::error( $result[ 'message' ] );
			}
			else
			{
				$error_msg = InstagramApi::error();
			}

			Helper::response( FALSE, $error_msg );
		}

		$account_id      = $result[ 'logged_in_user' ][ 'pk' ];
		$profile_pic_url = $result[ 'logged_in_user' ][ 'profile_pic_url' ];

		$password = '(-F-S-P-)' . str_rot13( base64_encode( $password . '(-F-S-P-)' . Date::epoch() ) );

		if ( ! get_current_user_id() > 0 )
		{
			Helper::response( FALSE, fsp__( 'The current WordPress user ID is not available. Please, check if your security plugins prevent user authorization.' ) );
		}

		$sqlData = [
			'blog_id'     => Helper::getBlogId(),
			'user_id'     => get_current_user_id(),
			'profile_id'  => $account_id,
			'username'    => $username,
			'password'    => $password,
			'proxy'       => $proxy,
			'driver'      => 'instagram',
			'name'        => $username,
			'profile_pic' => $profile_pic_url
		];

		$checkIfExists = DB::fetch( 'accounts', [
			'blog_id'    => Helper::getBlogId(),
			'user_id'    => get_current_user_id(),
			'profile_id' => $account_id,
			'driver'     => 'instagram'
		] );

		if ( $checkIfExists )
		{
			DB::DB()->update( DB::table( 'accounts' ), $sqlData, [ 'id' => $checkIfExists[ 'id' ] ] );
		}
		else
		{
			DB::DB()->insert( DB::table( 'accounts' ), $sqlData );
		}

		Helper::response( TRUE );
	}

	/**
	 * @param $photo_path
	 * @param $title
	 *
	 * @return array
	 */
	private static function imageForStory ( $photo_path, $title )
	{
		$storyBackground    = Helper::getOption( 'instagram_story_background', '636e72' );
		$titleBackground    = Helper::getOption( 'instagram_story_title_background', '000000' );
		$titleBackgroundOpc = Helper::getOption( 'instagram_story_title_background_opacity', '30' );
		$titleColor         = Helper::getOption( 'instagram_story_title_color', 'FFFFFF' );
		$titleTop           = (int) Helper::getOption( 'instagram_story_title_top', '125' );
		$titleLeft          = (int) Helper::getOption( 'instagram_story_title_left', '30' );
		$titleWidth         = (int) Helper::getOption( 'instagram_story_title_width', '660' );
		$titleFontSize      = (int) Helper::getOption( 'instagram_story_title_font_size', '30' );
		$titleRtl           = Helper::getOption( 'instagram_story_title_rtl', 'off' ) == 'on';

		if ( $titleRtl )
		{
			$p_a   = new PersianStringDecorator();
			$title = $p_a->decorate( $title, FALSE, TRUE );
		}

		$titleBackgroundOpc = $titleBackgroundOpc > 100 || $titleBackgroundOpc < 0 ? 0.3 : $titleBackgroundOpc / 100;

		$storyBackground   = Helper::hexToRgb( $storyBackground );
		$storyBackground[] = 0;// opacity

		$storyW = 1080 / 1.5;
		$storyH = 1920 / 1.5;

		$imageInf    = new PHPImage( $photo_path );
		$imageWidth  = $imageInf->getWidth();
		$imageHeight = $imageInf->getHeight();

		if ( $imageWidth * $imageHeight > 3400 * 3400 ) // large file
		{
			return NULL;
		}

		$imageInf->cleanup();
		unset( $imageInf );

		$w1 = $storyW;
		$h1 = ( $w1 / $imageWidth ) * $imageHeight;

		if ( $h1 > $storyH )
		{
			$w1 = ( $storyH / $h1 ) * $w1;
			$h1 = $storyH;
		}

		$image = new PHPImage();
		$image->initialiseCanvas( $storyW, $storyH, 'img', $storyBackground );

		$image->draw( $photo_path, '50%', '50%', $w1, $h1 );

		$titleLength  = mb_strlen( $title, 'UTF-8' );
		$titlePercent = $titleLength - 40;
		if ( $titlePercent < 0 )
		{
			$titlePercent = 0;
		}
		else if ( $titlePercent > 100 )
		{
			$titlePercent = 100;
		}

		// write title
		if ( ! empty( $title ) )
		{
			$textPadding = 10;
			$textWidth   = $titleWidth;
			$textHeight  = 100 + $titlePercent;
			$iX          = $titleLeft;
			$iY          = $titleTop;

			$image->setFont( __DIR__ . '/../PHPImage/font/arial.ttf' );
			$image->rectangle( $iX, $iY, $textWidth + $textPadding, $textHeight - $textPadding, Helper::hexToRgb( $titleBackground ), $titleBackgroundOpc );

			$image->textBox( $title, [
				'fontSize'        => $titleFontSize,
				'fontColor'       => Helper::hexToRgb( $titleColor ),
				'x'               => $iX,
				'y'               => $iY,
				'strokeWidth'     => 1,
				'strokeColor'     => [ 99, 110, 114 ],
				'width'           => $textWidth,
				'height'          => $textHeight,
				'alignHorizontal' => 'center',
				'alignVertical'   => 'center'
			] );
		}

		$newFileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid( 'fs_' );
		static::moveToTrash( $newFileName );

		$image->setOutput( 'jpg' )->save( $newFileName );

		return [
			'width'  => $storyW,
			'height' => $storyH,
			'path'   => $newFileName
		];
	}

	private static function imageForFeed ( $photo )
	{
		$result = @getimagesize( $photo );

		if ( $result === FALSE )
		{
			throw new InvalidArgumentException( sprintf( 'The photo file "%s" is not a valid image.', $photo ) );
		}

		$width  = $result[ 0 ];
		$height = $result[ 1 ];

		$ratio1    = $width / $height;
		$newWidth  = $width;
		$newHeight = $height;

		if ( $ratio1 > self::MAX_ASPECT_RATIO )
		{
			$newWidth = (int) ( $height * self::MAX_ASPECT_RATIO );
		}
		else if ( $ratio1 < self::MIN_ASPECT_RATIO )
		{
			$newHeight = (int) ( $width / self::MIN_ASPECT_RATIO );
		}

		$image = new PHPImage();
		$image->initialiseCanvas( $newWidth, $newHeight, 'img', [ 255, 255, 255, 0 ] );

		$image->draw( $photo );

		$newFileName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid( 'fs_' );
		static::moveToTrash( $newFileName );

		$image->setOutput( 'jpg' )->save( $newFileName );

		return [
			'width'  => $newWidth,
			'height' => $newHeight,
			'path'   => $newFileName
		];
	}

	private static function renderVideo ( $video_path, $target )
	{
		if ( @exec( 'echo EXEC' ) !== 'EXEC' )
		{
			throw new Exception( fsp__( 'exec() function have to be enabled to share videos on Instagram. <a href=\'https://www.fs-poster.com/documentation/how-to-install-ffmpeg\' target=\'_blank\'>How to?</a>', [], FALSE ) );
		}

		$details = FFmpeg::videoDetails( $video_path );

		$width       = $details[ 'width' ];
		$height      = $details[ 'height' ];
		$duration    = (int) $details[ 'duration' ];
		$video_codec = (int) $details[ 'video_codec' ];
		$audio_codec = (int) $details[ 'audio_codec' ];

		$maxDuration = ( $target == 'story' ? 15 : 60 ) - 0.1;
		$minDuration = ( $target == 'story' ? 1 : 3 );

		if ( $duration < $minDuration )
		{
			throw new Exception( 'Video is too short!' );
		}

		$ratio1 = $width / $height;

		if ( $ratio1 > self::MAX_ASPECT_RATIO )
		{
			$newWidth  = (int) ( $height * self::MAX_ASPECT_RATIO );
			$newHeight = $height;
			$cropVideo = TRUE;
		}
		else if ( $ratio1 < self::MIN_ASPECT_RATIO )
		{
			$newWidth  = $width;
			$newHeight = (int) ( $width / self::MIN_ASPECT_RATIO );
			$cropVideo = TRUE;
		}
		else
		{
			$newWidth  = $width;
			$newHeight = $height;
			$cropVideo = FALSE;
		}

		$x = abs( $width - $newWidth ) / 2;
		$y = abs( $height - $newHeight ) / 2;

		$video_new_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid( 'fs_' ) . '.mp4';
		static::moveToTrash( $video_new_path );

		$thumbnail = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid( 'fs_' ) . '.jpg';
		static::moveToTrash( $thumbnail );

		$ffmpeg = FFmpeg::factory();

		$outputFilters = [
			'-metadata:s:v rotate=""',
			'-f mp4',
			'-c:v libx264 -preset fast -crf 24'
		];

		if ( $audio_codec !== 'aac' )
		{
			if ( $ffmpeg->hasLibFdkAac() )
			{
				$outputFilters[] = '-c:a libfdk_aac -vbr 4';
			}
			else
			{
				$outputFilters[] = '-strict -2 -c:a aac -b:a 96k';
			}
		}
		else
		{
			$outputFilters[] = '-c:a copy';
		}

		if ( $duration > $maxDuration )
		{
			$outputFilters[] = sprintf( '-t %.2F', $maxDuration );
		}

		$command = sprintf( '-y -i %s -vf %s %s %s', FFmpeg::escape( $video_path ), FFmpeg::escape( sprintf( 'crop=w=%d:h=%d:x=%d:y=%d', $newWidth, $newHeight, $x, $y ) ), implode( ' ', $outputFilters ), FFmpeg::escape( $video_new_path ) );

		$commandForThumbnail = sprintf( '-y -i %s -f mjpeg -vframes 1 -ss 00:00:00.000 %s', FFmpeg::escape( $video_path ), FFmpeg::escape( $thumbnail ) );

		$ffmpegOutput          = $ffmpeg->run( $command );
		$ffmpegOutputThumbnail = $ffmpeg->run( $commandForThumbnail );

		return [
			'width'       => $width,
			'height'      => $height,
			'duration'    => $duration,
			'audio_codec' => $audio_codec,
			'vudie_codec' => $video_codec,
			'path'        => $video_new_path,
			'thumbnail'   => self::imageForFeed( $thumbnail )
		];
	}

	private static function isCookieMethod ( $accountInf )
	{
		return $accountInf[ 'password' ] == '*****';
	}

	private static function getCookies ( $accountInfo )
	{
		$accountSess = DB::fetch( 'account_sessions', [
			'driver'   => 'instagram',
			'username' => $accountInfo[ 'username' ]
		] );

		return json_decode( $accountSess[ 'cookies' ], TRUE );
	}

	public static function moveToTrash ( $filePath )
	{
		self::$recycle_bin[] = $filePath;
	}

	public static function error ( $error_msg = NULL, $account_id = NULL )
	{
		if ( $err_obj = json_decode( $error_msg, TRUE ) )
		{
			$error_msg = isset( $err_obj[ 'message' ] ) ? $err_obj[ 'message' ] : $error_msg;
		}

		if ( isset( $error_msg ) && ! empty( $error_msg ) )
		{
			if ( $error_msg === 'login_required' && $account_id )
			{
				AccountService::disable_account( $account_id, fsp__( 'The account is disconnected from the plugin. Please add your account to the plugin again by getting the cookie on the browser <a href=\'https://www.fs-poster.com/documentation/fs-poster-schedule-auto-publish-wordpress-posts-to-instagram\' target=\'_blank\'>Incognito mode</a>. And close the browser without logging out from the account.', [], FALSE ) );

				return fsp__( 'The account is disconnected from the plugin. Please add your account to the plugin again by getting the cookie on the browser <a href=\'https://www.fs-poster.com/documentation/fs-poster-schedule-auto-publish-wordpress-posts-to-instagram\' target=\'_blank\'>Incognito mode</a>. And close the browser without logging out from the account.', [], FALSE );
			}
			else
			{
				return esc_html( $error_msg );
			}
		}
		else
		{
			return fsp__( 'An error occurred while processing the request!' );
		}
	}
}