/**
 * Promotional Banner Script
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle banner dismissal
        $('.abs-promo-banner-close').on('click', function() {
            var $banner = $(this).closest('.abs-promo-banner');

            // Add dismissing animation
            $banner.addClass('abs-promo-banner-dismissing');

            // Remove banner after animation
            setTimeout(function() {
                $banner.remove();
            }, 300);

            // Set cookie to remember dismissal (24 hours)
            document.cookie = 'abs_promo_banner_dismissed=1; path=/; max-age=86400';
        });
    });

})(jQuery);
