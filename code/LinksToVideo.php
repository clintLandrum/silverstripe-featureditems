<?php
/**
 * Extension to easily allow a dataobject to link to a youtube or vimeo video.
 * This adds DB fields, but does not add any ui to the cms, rather you should do
 * something like this in getCMSFields:
 *
 * $fields->addFieldsToTab('Root.Video', $this->getCMSFieldsForVideo());
 *
 * This allows you to put them wherever you want (special tab, composite field, the main tab, etc).
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 10.16.2013
 * @package featureditems
 */
class LinksToVideo extends DataExtension
{
	private static $db = array(
		'VideoURL'      => 'Varchar(255)',
		'VideoThumbURL' => 'Varchar(255)',
		'YouTubeID'     => 'Varchar(255)',
		'VimeoID'       => 'Varchar(255)',
	);


	/**
	 * @return array
	 */
	public function getCMSFieldsForVideo() {
		return array(
			TextField::create('VideoURL', 'Video URL'),
			ReadonlyField::create('VideoType', 'Detected Type'),
			LiteralField::create('VideoThumb', <<<HTML
	            <div id="VideoThumbnail" class="field readonly">
	                <label class="left" for="Form_ItemEditForm_VideoThumbnail">Thumbnail</label>
	                <div class="middleColumn">
	                    <span id="Form_ItemEditForm_VideoThumbnail" class="readonly">
	                        {$this->getVideoThumbnail()}
	                    </span>
	                </div>
	            </div>
HTML
			),
			LiteralField::create('VideoEmbed', <<<HTML
	            <div id="VideoPreview" class="field readonly">
	                <label class="left" for="Form_ItemEditForm_VideoPreview">Preview</label>
	                <div class="middleColumn">
	                    <span id="Form_ItemEditForm_VideoPreview" class="readonly">
	                        {$this->getVideoEmbedCode()}
	                    </span>
	                </div>
	            </div>
HTML
			),
		);
	}


	/**
	 * @return string
	 */
	public function getVideoEmbedCode() {
		if ($id = $this->owner->YouTubeID) {
			return '<div class="flex-video"><iframe width="420" height="315" src="http://www.youtube.com/embed/' . $id . '" frameborder="0" allowfullscreen></iframe></div>';
		} elseif ($id = $this->owner->VimeoID) {
			return '<div class="flex-video widescreen vimeo"><iframe src="http://player.vimeo.com/video/' . $id . '?title=0&amp;byline=0&amp;portrait=0" width="400" height="225" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe></div>';
		} else {
			return '<div class="unknown-video"><a href="' . $this->owner->URL . '" target="_blank">View Video</a></div>';
		}
	}


	/**
	 * @return string
	 */
	public function getVideoType() {
		if ($this->owner->YouTubeID) return 'YouTube';
		if ($this->owner->VimeoID) return 'Vimeo';
		return 'Unknown';
	}


	/**
	 * @return string
	 */
	protected function extractYouTubeID() {
		if (preg_match("#(?<=v=)[a-zA-Z0-9-]+(?=&)|(?<=v\/)[^&\n]+(?=\?)|(?<=v=)[^&\n]+|(?<=youtu.be/)[^&\n]+#", $this->owner->VideoURL, $matches)) {
			Debug::dump($matches);
			return $matches[0];
		} else {
			return '';
		}
	}


	/**
	 * @return string
	 */
	protected function extractVimeoID() {
		if (preg_match('#https?://vimeo.com/([0-9]+)#i', $this->owner->VideoURL, $matches)) {
			return $matches[1];
		} else {
			return '';
		}
	}


	/**
	 * @return string
	 */
	protected function retrieveThumbnailURL() {
		if ($this->owner->YouTubeID) {
			return 'http://img.youtube.com/vi/' . $this->owner->YouTubeID . '/1.jpg';
		} elseif ($this->owner->VimeoID) {
			$url        = 'http://vimeo.com/api/v2/video/' . $this->owner->VimeoID . '.php';
			$contents   = @file_get_contents($url);
			$thumb      = @unserialize(trim($contents));
			if (is_array($thumb)) return $thumb[0]['thumbnail_small'];
		}
	}


	/**
	 * @return string
	 */
	public function getVideoThumbnail() {
		return $this->owner->VideoThumbURL
			? '<img src="' . $this->owner->VideoThumbURL . '" class="video-thumb">'
			: '<span class="video-thumb no-image">No Image</span>';
	}


	/**
	 * Extract the youtube/vimeo id if possible
	 */
	public function onBeforeWrite() {
		if ($this->owner->isChanged('VideoURL')) {
			$this->owner->YouTubeID     = $this->extractYouTubeID();
			$this->owner->VimeoID       = $this->extractVimeoID();
			$this->owner->VideoThumbURL = $this->retrieveThumbnailURL();
		}
	}
}