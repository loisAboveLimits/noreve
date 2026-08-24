jQuery(document).ready(function ($) {
    // Selectors for Apple Pay settings
    const themeSelect = $('#woocommerce_moyasar_express_btn_theme');
    const heightInput = $('#woocommerce_moyasar_express_btn_height');
    const labelSelect = $('#woocommerce_moyasar_express_btn_label');
    const previewBtn = $('.moyasar-btn-preview .button');
    const previewBtnText = $('.moyasar-btn-preview .button .moyasar-apple-text');
    const previewBtnIcon = $('.moyasar-btn-preview .button .moyasar-apple-icon');

    function updatePreview() {
        // Theme
        const theme = themeSelect.val();
        if (theme === 'white') {
            previewBtn.css({
                'background-color': '#fff',
                'color': '#000',
                'border': '1px solid #dcdcde' // Minimal border for visibility
            });
        } else if (theme === 'white-outline') {
            previewBtn.css({
                'background-color': '#fff',
                'color': '#000',
                'border': '1px solid #000'
            });
        } else { // black or default
            previewBtn.css({
                'background-color': '#000',
                'color': '#fff',
                'border': 'none'
            });
        }

        // Height (Presets)
        let heightVal = heightInput.val();
        let heightPx = '44px'; // Default/Medium

        if (heightVal === 'small') {
            heightPx = '32px';
        } else if (heightVal === 'large') {
            heightPx = '55px';
        }

        previewBtn.css('height', heightPx);

        // Label
        const label = labelSelect.val();
        let text = 'Pay'; // default

        switch (label) {
            case 'plain': text = ''; break;
            case 'buy': text = 'Buy'; break;
            case 'donate': text = 'Donate'; break;
            case 'check-out': text = 'Check Out'; break;
            case 'book': text = 'Book'; break;
            case 'subscribe': text = 'Subscribe'; break;
            case 'reload': text = 'Reload'; break;
            case 'add-money': text = 'Add Money'; break;
            case 'top-up': text = 'Top Up'; break;
            case 'order': text = 'Order'; break;
            case 'rent': text = 'Rent'; break;
            case 'support': text = 'Support'; break;
            case 'contribute': text = 'Contribute'; break;
            case 'tip': text = 'Tip'; break;
            default: text = 'Pay';
        }

        if (text === '') {
            previewBtnText.hide();
            previewBtnIcon.css('margin-right', '0');
        } else {
            previewBtnText.text(text).show();
            // restore space
            previewBtnIcon.css('margin-right', '4px');
        }
    }

    // Bind events
    themeSelect.on('change', updatePreview);
    heightInput.on('input change', updatePreview);
    labelSelect.on('change', updatePreview);

    // Initial call
    updatePreview();
});

// Webhook Helpers
function moyasarCopyInput(id) {
    var copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 99999); /* For mobile devices */

    try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'Copied!' : 'Failed';

        // Find the button next to the input
        var btn = jQuery(copyText).next('button');
        var originalText = btn.text();
        btn.text(msg);
        setTimeout(function () {
            btn.text(originalText);
        }, 2000);
    } catch (err) {
        console.error('Fallback: Oops, unable to copy', err);
    }
}

function moyasarToggleSecret(id) {
    var x = document.getElementById(id);
    var btn = jQuery(x).next('button');

    // In PHP: Input -> Show -> Copy
    // Next to input is Show btn.

    if (x.type === "password") {
        x.type = "text";
        jQuery(btn).text('Hide');
    } else {
        x.type = "password";
        jQuery(btn).text('Show');
    }
}

