<?php

namespace FSPoster\App\Pages\Settings\Views;

use FSPoster\App\Providers\Pages;
use FSPoster\App\Providers\Helper;

defined( 'ABSPATH' ) or exit;
?>

<link rel="stylesheet" href="<?php echo Pages::asset( 'Settings', 'css/fsp-shared-preview.css' ); ?>">
<link rel="stylesheet" href="<?php echo Pages::asset( 'Settings', 'css/fsp-instagram-preview.css' ); ?>">

<div class="fsp-settings-row">
	<div class="fsp-settings-col">
		<div class="fsp-settings-label-text"><?php echo fsp__( 'Share Instagram posts on' ); ?></div>
		<div class="fsp-settings-label-subtext"><?php echo fsp__( 'Select where to share posts on Instagram.' ); ?></div>
	</div>
	<div class="fsp-settings-col">
		<select name="fs_instagram_post_in_type" class="fsp-form-select">
			<option value="1"<?php echo Helper::getOption( 'instagram_post_in_type', '1' ) == '1' ? ' selected' : ''; ?>>Profile only</option>
			<option value="2"<?php echo Helper::getOption( 'instagram_post_in_type', '1' ) == '2' ? ' selected' : ''; ?>>Story only</option>
			<option value="3"<?php echo Helper::getOption( 'instagram_post_in_type', '1' ) == '3' ? ' selected' : ''; ?>>Profile and Story</option>
		</select>
	</div>
</div>
<div class="fsp-settings-row">
	<div class="fsp-settings-col">
		<div class="fsp-settings-label-text"><?php echo fsp__( 'Share the post link on Instagram story' ); ?></div>
		<div class="fsp-settings-label-subtext"><?php echo fsp__( 'When you share a post on Instagram story, the post link will be shared automatically. For this, your account has to be a business account with more than 10k followers, and you have to add the account using the login & Pass method.' ); ?></div>
	</div>
	<div class="fsp-settings-col">
		<div class="fsp-toggle">
			<input type="checkbox" name="fs_instagram_story_link" class="fsp-toggle-checkbox" id="fs_instagram_story_link"<?php echo Helper::getOption( 'instagram_story_link', '1' ) ? ' checked' : ''; ?>>
			<label class="fsp-toggle-label" for="fs_instagram_story_link"></label>
		</div>
	</div>
</div>
<div class="fsp-settings-row">
	<div class="fsp-settings-col">
		<div class="fsp-settings-label-text"><?php echo fsp__( 'Cut the exceeded part of the custom message' ); ?></div>
		<div class="fsp-settings-label-subtext"><?php echo fsp__( 'Instagram limits the post content length to a maximum of 2200 characters. By enabling the option, the first 2200 characters of your custom message will be shared; otherwise, the post won\'t be shared because of the limit.' ); ?></div>
	</div>
	<div class="fsp-settings-col">
		<div class="fsp-toggle">
			<input type="checkbox" name="fs_instagram_autocut_text" class="fsp-toggle-checkbox" id="fs_instagram_autocut_text" <?php echo Helper::getOption( 'instagram_autocut_text', '1' ) ? 'checked' : ''; ?>>
			<label class="fsp-toggle-label" for="fs_instagram_autocut_text"></label>
		</div>
	</div>
</div>
<div class="fsp-settings-row fsp-is-collapser">
	<div class="fsp-settings-collapser">
		<div class="fsp-settings-label-text"><?php echo fsp__( 'Custom post message' ); ?>
			<i class="fas fa-angle-up fsp-settings-collapse-state fsp-is-rotated"></i>
		</div>
		<div class="fsp-settings-label-subtext"><?php echo fsp__( 'You can customize the text of the shared post as you like by using the current keywords. Please read the <a href="https://www.fs-poster.com/documentation/instagram-empty-caption" target="_blank">Instagram content rules</a> before setting your custom message. Instagram removes captions from the images when you exceed Instagram limits.', [], FALSE ); ?></div>
	</div>
	<div class="fsp-settings-collapse">
		<div class="fsp-settings-col">
			<div class="fsp-settings-col-title"><?php echo fsp__( 'Text' ); ?></div>
			<div class="fsp-custom-post" data-preview="fspCustomPostPreview1">
				<textarea name="fs_post_text_message_instagram" class="fsp-form-textarea"><?php echo esc_html( Helper::getOption( 'post_text_message_instagram', "{title}" ) ); ?></textarea>
				<div class="fsp-custom-post-buttons">
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{id}">
						{ID}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post ID' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{author}">
						{AUTHOR}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post author name' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{content_short_40}">
						{CONTENT_SHORT_40}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'The default is the first 40 characters. You can set the number whatever you want. The plugin will share that number of characters.' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{title}">
						{TITLE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post title' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{featured_image_url}">
						{FEATURED_IMAGE_URL}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Featured image URL' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{tags}">
						{TAGS}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post Tags' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{product_regular_price}">
						{PRODUCT_REGULAR_PRICE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'WooCommerce - product price' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{product_sale_price}">
						{PRODUCT_SALE_PRICE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'WooCommerce - product sale price' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{product_current_price}">
						{PRODUCT_CURRENT_PRICE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'WooCommerce - the current price of product' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{content_full}">
						{CONTENT_FULL}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post full content' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{short_link}">
						{SHORT_LINK}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post short link' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{excerpt}">
						{EXCERPT}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post excerpt' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{categories}">
						{CATEGORIES}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post Categories' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{uniq_id}">
						{UNIQ_ID}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Unique ID' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{cf_KEY}">
						{CF_KEY}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Custom fields. Replace KEY with the custom field name.' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{link}">
						{LINK}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post link' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-red fsp-clear-button fsp-tooltip" data-title="<?php echo fsp__( 'Click to clear the textbox' ); ?>">
						<?php echo fsp__( 'CLEAR' ); ?>
					</button>
				</div>
			</div>
		</div>
		<div class="fsp-settings-col">
			<div class="fsp-settings-col-title"><?php echo fsp__( 'Preview' ); ?></div>
			<div class="fsp-settings-preview">
				<div class="fsp-settings-preview-header">
					<img src="#" onerror="FSPoster.no_photo( this );">
					<div class="fsp-settings-preview-header-title"><?php echo get_bloginfo( 'name' ); ?></div>
					<i class="fas fa-ellipsis-h fsp-settings-preview-dots"></i>
				</div>
				<div class="fsp-settings-preview-image"></div>
				<div class="fsp-settings-preview-footer">
					<div class="fsp-settings-preview-controls">
						<i class="far fa-heart"></i>
						<i class="far fa-comment"></i>
						<i class="far fa-paper-plane"></i>
					</div>
					<div class="fsp-settings-preview-info">
						<span class="fsp-settings-preview-info-title"><?php echo get_bloginfo( 'name' ); ?></span>
						<span id="fspCustomPostPreview1" class="fsp-settings-preview-info-text"><?php echo esc_html( Helper::getOption( 'post_text_message_instagram', "{title}" ) ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="fsp-settings-row fsp-is-collapser">
	<div class="fsp-settings-collapser">
		<div class="fsp-settings-label-text"><?php echo fsp__( 'Customize story' ); ?>
			<i class="fas fa-angle-up fsp-settings-collapse-state fsp-is-rotated"></i>
		</div>
		<div class="fsp-settings-label-subtext"><?php echo fsp__( 'You can customize the text of the story as you like by using the current keywords. You can also customize the appearance of the story using available options.' ); ?></div>
	</div>
	<div class="fsp-settings-collapse">
		<div id="fspSettingsIgStoryMessageRow" class="fsp-settings-col fsp-flex-grow-1">
			<div class="fsp-settings-igstory-tabs">
				<div id="fspSettingsIgStoryMessageTab" class="fsp-settings-igstory-tab fsp-is-active" data-tab="message">
					<?php echo fsp__( 'Customize message', [], FALSE ); ?>
				</div>
				<div id="fspSettingsIgStoryAppearanceTab" class="fsp-settings-igstory-tab" data-tab="appearance">
					<?php echo fsp__( 'Customize appearance', [], FALSE ); ?>
				</div>
			</div>
			<div class="fsp-settings-col-title"><?php echo fsp__( 'Text' ); ?></div>
			<div class="fsp-custom-post" data-preview="fspStoryTitle">
				<textarea name="fs_post_text_message_instagram_h" class="fsp-form-textarea"><?php echo esc_html( Helper::getOption( 'post_text_message_instagram_h', "{title}" ) ); ?></textarea>
				<div class="fsp-custom-post-buttons">
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{id}">
						{ID}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post ID' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{product_regular_price}">
						{PRODUCT_REGULAR_PRICE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'WooCommerce - product price' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{author}">
						{AUTHOR}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post author name' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{content_short_40}">
						{CONTENT_SHORT_40}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'The default is the first 40 characters. You can set the number whatever you want. The plugin will share that number of characters.' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{title}">
						{TITLE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post title' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{featured_image_url}">
						{FEATURED_IMAGE_URL}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Featured image URL' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{tags}">
						{TAGS}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post Tags' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{product_sale_price}">
						{PRODUCT_SALE_PRICE}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'WooCommerce - product sale price' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{content_full}">
						{CONTENT_FULL}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post full content' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{short_link}">
						{SHORT_LINK}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post short link' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{excerpt}">
						{EXCERPT}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post excerpt' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{categories}">
						{CATEGORIES}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post Categories' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{uniq_id}">
						{UNIQ_ID}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Unique ID' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{cf_KEY}">
						{CF_KEY}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Custom fields. Replace KEY with the custom field name.' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-gray fsp-append-to-text" data-key="{link}">
						{LINK}
						<i class="fas fa-info-circle fsp-tooltip" data-title="<?php echo fsp__( 'Post link' ); ?>"></i>
					</button>
					<button type="button" class="fsp-button fsp-is-red fsp-clear-button fsp-tooltip" data-title="<?php echo fsp__( 'Click to clear the textbox' ); ?>">
						<?php echo fsp__( 'CLEAR' ); ?>
					</button>
				</div>
			</div>
		</div>
		<div id="fspSettingsIgStoryAppearanceRow" class="fsp-settings-col fsp-flex-grow-1 fsp-hide">
			<div class="fsp-settings-igstory-tabs">
				<div class="fsp-settings-igstory-tab" data-tab="message">
					<?php echo fsp__( 'Customize message', [], FALSE ); ?>
				</div>
				<div class="fsp-settings-igstory-tab fsp-is-active" data-tab="appearance">
					<?php echo fsp__( 'Customize appearance', [], FALSE ); ?>
				</div>
			</div>
			<div class="fsp-settings-igcontrols">
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Story background color:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span id="fspStoryBgInput">#</span>
						<input data-jscolor="{ previewElement: '' }" data-type="story-background" autocomplete="off" class="fsp-form-input" name="fs_instagram_story_background" value="<?php echo Helper::getOption( 'instagram_story_background', 'ED5458' ); ?>">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title background:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span id="fspStoryTitleBgInput">#</span>
						<input autocomplete="off" data-jscolor="{ previewElement: '' }" class="fsp-form-input jscolor" name="fs_instagram_story_title_background" value="<?php echo Helper::getOption( 'instagram_story_title_background', 'FFFFFF' ); ?>" data-type="title-background-color">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title background opacity:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span>%</span>
						<input autocomplete="off" class="fsp-form-input" name="fs_instagram_story_title_background_opacity" value="<?php echo Helper::getOption( 'instagram_story_title_background_opacity', '30' ); ?>" data-type="title-background-opacity">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title color:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span id="fspStoryTitleColorInput">#</span>
						<input autocomplete="off" data-jscolor="{ previewElement: '' }" class="fsp-form-input" name="fs_instagram_story_title_color" value="<?php echo Helper::getOption( 'instagram_story_title_color', 'FFFFFF' ); ?>" data-type="title-color">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title top offset:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span>px</span>
						<input autocomplete="off" class="fsp-form-input" name="fs_instagram_story_title_top" value="<?php echo Helper::getOption( 'instagram_story_title_top', '125' ); ?>" data-type="title-top">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title left offset:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span>px</span>
						<input autocomplete="off" class="fsp-form-input" name="fs_instagram_story_title_left" value="<?php echo Helper::getOption( 'instagram_story_title_left', '30' ); ?>" data-type="title-left">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title width:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span>px</span>
						<input autocomplete="off" class="fsp-form-input" name="fs_instagram_story_title_width" value="<?php echo Helper::getOption( 'instagram_story_title_width', '660' ); ?>" data-type="title-width">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-settings-igcontrol-label"><?php echo fsp__( 'Title font size:' ); ?></div>
					<div class="fsp-settings-igcontrol-input">
						<span>px</span>
						<input autocomplete="off" class="fsp-form-input" name="fs_instagram_story_title_font_size" value="<?php echo Helper::getOption( 'instagram_story_title_font_size', '30' ); ?>" data-type="title-font-size">
					</div>
				</div>
				<div class="fsp-settings-igcontrol">
					<div class="fsp-form-checkbox-group">
						<input id="fspRTLDirection" type="checkbox" class="fsp-form-checkbox" name="fs_instagram_story_title_rtl" <?php echo Helper::getOption( 'instagram_story_title_rtl', 'off' ) == 'on' ? 'checked' : ''; ?>>
						<label for="fspRTLDirection">
							<?php echo fsp__( 'RTL direction' ); ?>
						</label>
					</div>
				</div>
			</div>
		</div>
		<div class="fsp-settings-col fsp-settings-igstory-container">
			<div id="fspStory" class="fsp-settings-igstory">
				<div id="fspStoryTitle" class="fsp-settings-igstory-title"><?php echo esc_html( Helper::getOption( 'post_text_message_instagram_h', "{title}" ) ); ?></div>
				<div class="fsp-settings-igstory-image"></div>
			</div>
		</div>
	</div>
</div>