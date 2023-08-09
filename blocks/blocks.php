<?php
require_once dirname( __FILE__ ) . '/coauthors/class-cap-block-coauthors.php';
require_once dirname( __FILE__ ) . '/coauthor-avatar/class-cap-block-coauthor-avatar.php';
require_once dirname( __FILE__ ) . '/coauthor-description/class-cap-block-coauthor-description.php';
require_once dirname( __FILE__ ) . '/coauthor-display-name/class-cap-block-coauthor-display-name.php';
require_once dirname( __FILE__ ) . '/coauthor-feature-image/class-cap-block-coauthor-feature-image.php';

new CAP_Block_CoAuthors();
new CAP_Block_CoAuthor_Avatar();
new CAP_Block_CoAuthor_Description();
new CAP_Block_CoAuthor_Display_Name();
new CAP_Block_CoAuthor_Feature_Image();
