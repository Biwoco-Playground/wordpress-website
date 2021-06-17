<?php

use \wpautoterms\frontend\notice\Cookies_Notice;

?>
<div class="<?php echo $class_escaped; ?>" style="display:none">
	<?php echo $message; ?>
    <a href="javascript:void(0);" class="<?php echo esc_attr( Cookies_Notice::CLASS_CLOSE_BUTTON ); ?>"
       data-value="1" data-cookie="<?php echo esc_attr( $cookie_name ); ?>">
		<?php echo $close; ?></a></div>
<script type="text/javascript">
    (function () {
        function getCookie(name) {
            name = name.toLowerCase();
            var cookies = document.cookie.split(';');
            for (var k in cookies) {
                var el = cookies[k].split('=');
                if (el[0].trim().toLowerCase() === name) {
                    return el[1];
                }
            }
            return null;
        }

        var disable = typeof wpautoterms_js_cookies_notice !== "undefined" && wpautoterms_js_cookies_notice.disable;
        if (!disable && getCookie("<?php echo $cookie_name; ?>") != 1) {
            var entries = document.querySelectorAll(".<?php echo $class_escaped; ?>");
            for (var k = 0; k < entries.length; ++k) {
                var el = entries[k];
                el.style.display = null;
            }
        }
    })();
</script>
