<?php
/**
* Created by Eighty / 20 Results, owned by Wicked Strong Chicks, LLC.
* Developer: Thomas Sjolshagen <thomas@eigthy20results.com>
*/

$view = isset($_REQUEST['page'])? sanitize_text_field($_REQUEST['page']) : ""; ?>

<div class="wrap e20rbpc-admin">
	<div class="e20rbpc-banner">
		<a class="e20r-logo" title="Eighty / 20 Results - Blur Protected Content Plugin for WordPress" target="_blank" href="https://eighty20results.com">
            <img src="<?php echo E20R_BPC_PLUGIN_URL; ?>/images/Eighty20Results-Logo-small.png" height="75" border="0" alt="Eighty/20Results by Wicked Strong Chick, LLC (c) - All Rights Reserved" />
        </a>
        <div class="e20rbpc-meta">
            <span class="e20rbpc-grey">v<?php echo E20R_BPC_VER; ?></span>
            <a target="_blank" class="e20rbpc-tag-green" href="https://eighty20results.zendesk.com"><?php _e('Click for Plugin Support', 'e20rbpc');?></a>
        </div>
        <br style="clear:both;" />
    </div>

    <div id="e20rbpc-notifications">
    </div>
    <script>
        jQuery(document).ready(function() {
            jQuery.get('<?php echo get_admin_url(NULL, "/admin-ajax.php?action=e20rbpc_notifications"); ?>', function(data) {
                if(data && data != 'NULL')
                    jQuery('#e20rbpc_notifications').html(data);
            });
        });
    </script>
</div>