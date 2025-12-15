/* --------------------------------------------------------------------------
 * WooCommerce Select2 Kütüphanesini Ödeme Sayfasında Devre Dışı Bırakır
 * -------------------------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', 'disable_woocommerce_select2', 100 );
function disable_woocommerce_select2() {
    if ( class_exists( 'woocommerce' ) ) {
        if ( is_checkout() ) {
            wp_dequeue_style( 'select2' );
            wp_dequeue_script( 'select2' );
            wp_deregister_script( 'select2' );
            wp_deregister_style( 'select2' );
        }
    }
}

/* --------------------------------------------------------------------------
 * TÜM ADRES SAHALARINI SABİTLE
 * -------------------------------------------------------------------------- */

add_filter('woocommerce_checkout_fields', function($fields) {

    // Ülke → TR, gizle
    $fields['billing']['billing_country']['type'] = 'hidden';
    $fields['billing']['billing_country']['default'] = 'TR';

    // İl → Sivas, gizle
    $fields['billing']['billing_state']['type'] = 'hidden';
    $fields['billing']['billing_state']['default'] = 'TR58';

    // İlçe → Merkez, gizle
    $fields['billing']['billing_city']['type'] = 'hidden';
    $fields['billing']['billing_city']['default'] = 'Merkez';

    // "Farklı adrese gönder" tamamen kapat
    unset($fields['shipping']);

    // MAHALLE ALANI - AJAX TETİKLEYİCİ
    if (isset($fields['billing']['billing_neighborhood'])) {

        if (empty($fields['billing']['billing_neighborhood']['class'])) {
            $fields['billing']['billing_neighborhood']['class'] = [];
        }

        $fields['billing']['billing_neighborhood']['class'][] = 'update_totals_on_change';
    }

    return $fields;
});
add_filter('woocommerce_cart_needs_shipping_address', '__return_false');

/* --------------------------------------------------------------------------
 * MAHALLE ÜCRETİNİ OKU + SESSION'A YAZ
 * -------------------------------------------------------------------------- */
add_action('woocommerce_checkout_update_order_review', function($post_data) {

    parse_str($post_data, $data);

    if (!empty($data['billing_neighborhood'])) {
        WC()->session->set('billing_neighborhood', sanitize_text_field($data['billing_neighborhood']));
    }
});

/* --------------------------------------------------------------------------
 * SEPETE TESLİMAT ÜCRETİNİ EKLE
 * -------------------------------------------------------------------------- */

//ESKİ//
/*
add_action('woocommerce_cart_calculate_fees', function($cart) {

    if (is_admin() && !defined('DOING_AJAX')) return;

    $mahalle = WC()->session->get('billing_neighborhood');

    if (!$mahalle) return;

    // Fiyat listesi
    $fees = [
        'ABDULVAHABİGAZİ' => 50,
        'TUZLUGÖL'        => 55,
    ];

    if (!isset($fees[$mahalle])) return;

    // eski ücreti temizle
    foreach ($cart->get_fees() as $key => $fee) {
        if ($fee->name === 'Teslimat Ücreti') {
            unset($cart->fees_api()->fees[$key]);
        }
    }

    // yeni ücreti ekle
    $cart->add_fee('Teslimat Ücreti', $fees[$mahalle], false);
}, 9999);
*/

add_action('woocommerce_cart_calculate_fees', function($cart) {

    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    //SEPET SAYFASINDA HİÇ ÜCRET EKLEME
    if ( is_cart() ) {
        // Eski bir "Teslimat Ücreti" kalmışsa temizle
        foreach ($cart->get_fees() as $key => $fee) {
            if ($fee->name === 'Teslimat Ücreti') {
                unset($cart->fees_api()->fees[$key]);
            }
        }
        return;
    }

    //SADECE CHECKOUT'TA ÇALIŞSIN
    if ( ! is_checkout() ) {
        return;
    }

    // Mahalleyi session'dan al (billing_neighborhood)
    $mahalle = WC()->session->get('billing_neighborhood');
    if (!$mahalle) {
        return;
    }

    // Fiyat listesi
    $fees = [
        'ABDULVAHABİGAZİ' => 50,
        'TUZLUGÖL'        => 55,
    ];

    if (!isset($fees[$mahalle])) {
        return;
    }

    // Önceki ücreti temizle (checkout içinde mahalle değişirse)
    foreach ($cart->get_fees() as $key => $fee) {
        if ($fee->name === 'Teslimat Ücreti') {
            unset($cart->fees_api()->fees[$key]);
        }
    }

    // Yeni ücreti ekle
    $cart->add_fee('Teslimat Ücreti', $fees[$mahalle], false);

}, 9999);

/* --------------------------------------------------------------------------
 * CART SAYFASINDA DA HESAPLAMAYI ZORLA
 * -------------------------------------------------------------------------- */
add_filter('woocommerce_cart_ready_to_calc_shipping', '__return_true');

/* ============================================
   "Fatura Detayları" stringini "Teslimat Bilgileri" olarak değiştir
   ============================================ */
//
add_filter( 'gettext', function( $translated_text, $text, $domain ) {

    // WooCommerce dil alanında çalışıyoruz
    if ( $domain === 'woocommerce' ) {

        // "Fatura detayları" metnini yakala
        if ( trim( $translated_text ) === 'Faturalama ve Gönderim' ) {
            return ''; // istediğin başlığı buraya yaz
        }
    }

    return $translated_text;

}, 10, 3 );

add_filter( 'gettext', function( $translated_text, $text, $domain ) {

    // WooCommerce dil alanında çalışıyoruz
    if ( $domain === 'woocommerce' ) {

        // "Fatura detayları" metnini yakala
        if ( trim( $translated_text ) === 'Fatura adresi' ) {
            return 'Adres'; // istediğin başlığı buraya yaz
        }
    }

    return $translated_text;

}, 10, 3 );

/* --------------------------------------------------------------------------
 * SEPET SAYFASINDAKİ TÜM "GÖNDERİM" BLOĞUNU KALDIR
 * -------------------------------------------------------------------------- */
add_action( 'template_redirect', function() {
    if ( is_cart() ) {
        // Sepet sayfasında WooCommerce shipping kısmını tamamen kaldır
        remove_action( 'woocommerce_cart_totals_after_shipping', 'woocommerce_cart_totals_shipping_html' );
        remove_action( 'woocommerce_cart_totals_before_order_total', 'woocommerce_cart_totals_shipping_html' );
        remove_action( 'woocommerce_cart_totals_after_order_total', 'woocommerce_cart_totals_shipping_html' );
        add_filter( 'woocommerce_cart_ready_to_calc_shipping', '__return_false', 9999 );
    }
});

/* --------------------------------------------------------------------------
 * TELEFON NUMARASINI İSTENİLEN FORMATA GETİRME
 * -------------------------------------------------------------------------- */

add_action('wp_footer', function () {
    if (!is_checkout()) return;
    ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    // Buraya formatlanacak tüm telefon alanlarını ekle
    const fields = [
        'gonderen_telefon',
        'alici_telefon'
    ];

    function setupPhoneMask(selector) {
        const input = document.querySelector(`input[name="${selector}"]`);
        if (!input) return;

        // Varsayılan başlangıç
        input.value = "05";

        input.addEventListener("input", function() {
            let num = input.value.replace(/\D/g, "");

            // Mutlaka 05 ile başlamalı
            if (!num.startsWith("05")) {
                num = "05" + num.replace(/^0+/, "").replace(/^5+/, "");
            }

            // Maksimum 11 hane
            num = num.substring(0, 11);

            input.value = num;
        });
    }

    // Listedeki tüm alanlara maske uygula
    fields.forEach(field => setupPhoneMask(field));

});
</script>
    <?php
});

/* --------------------------------------------------------------------------
 * ÖDEME HATA MESAJLARI
 * -------------------------------------------------------------------------- */

add_filter('woocommerce_checkout_required_field_notice', function($message, $field_label, $field_name) {

	if ($field_name === 'alici_isim') {
        return 'Lütfen "ALICI" ismini giriniz.';
    }
    
	if ($field_name === 'alici_telefon') {
        return '"Lütfen "ALICI" telefon numarasını giriniz".';
    }

    if ($field_name === 'gonderen_isim') {
        return 'Lütfen "GÖNDEREN" ismini giriniz.';
    }
	
	if ($field_name === 'gonderen_telefon') {
        return 'Lütfen "GÖNDEREN" telefon numarasını giriniz.';
    }
	
	if ($field_name === 'billing_email') {
        return 'Lütfen e-posta adresi giriniz.';
    }
	
	if ($field_name === 'billing_neighborhood') {
		return 'Lütfen mahalle seçiniz.';
	}
	
	if ($field_name === 'adres') {
		return 'Lütfen teslimat adresini yazınız.';
	}
    return $message;
}, 10, 3);

// Ürün sayfasına "Hemen Satın Al" butonu ekle
add_action('woocommerce_after_add_to_cart_button', function() {
    global $product;

    // Sadece satın alınabilir ürünlerde göster
    if ( $product->is_purchasable() ) {

        $url = wc_get_cart_url() . '?add-to-cart=' . $product->get_id();

        echo '<a href="' . esc_url($url) . '" 
            class="button buy-now-button" 
            style="
                margin-left:0px;
                background:##bf1c34;
                color:white;
                padding:12px 0px;
                border-radius:0px;
                text-decoration:none;
                display:inline-block;
            ">
            Satın Al
        </a>';
    }
});

/*add_shortcode('custom_featured_slider', function () {

    ob_start();

    // WooCommerce ürün kategorilerini çek
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,   // içinde ürün olmayanları gösterme
        'parent'     => 0,      // sadece üst seviye kategoriler (istersen bunu kaldırabilirsin)
    ]);

    if (!empty($categories) && !is_wp_error($categories)) {

        echo '<div class="fp-wrapper">';
        echo '<button class="fp-prev">‹</button>';
		echo '<button class="fp-next">›</button>';
		echo '<div class="fp-track">';

        foreach ($categories as $category) {

            // Kategori görseli (thumbnail)
            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
            $image_html   = '';

            if ($thumbnail_id) {
                // WooCommerce varsayılan kategori görsel boyutu
                $image_html = wp_get_attachment_image($thumbnail_id, 'woocommerce_thumbnail');
            } else {
                // Görsel yoksa placeholder kullan
                if (function_exists('wc_placeholder_img')) {
                    $image_html = wc_placeholder_img('woocommerce_thumbnail');
                } else {
                    $image_html = '<img src="' . esc_url(wc_placeholder_img_src()) . '" alt="' . esc_attr($category->name) . '">';
                }
            }

            $category_link = get_term_link($category);

            echo '<div class="fp-item">';
            echo '<a href="'. esc_url($category_link) .'">';
            echo $image_html;
            echo '<h4 class="fp-title">'. esc_html($category->name) .'</h4>';
            // İstersen alt satırda kategori içindeki ürün sayısını da gösterebiliriz:
            echo '<p class="fp-price">'. intval($category->count) .' ÜRÜN</p>';
            echo '</a>';
            echo '</div>';
        }

        echo '</div></div>'; // fp-track + fp-wrapper
    }

    return ob_get_clean();
});

add_shortcode('featured_mobile_slider', function () {

    ob_start();

    $loop = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 10,
        'tax_query'      => [
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            ],
        ],
    ]);

    if ($loop->have_posts()) {

        echo '<div class="fp-wrapper">';
        echo '<button class="fp-prev">&#10094;</button>';
        echo '<button class="fp-next">&#10095;</button>';
        echo '<div class="fp-track">';

        while ($loop->have_posts()) {
            $loop->the_post();
            global $product;

            echo '<div class="fp-item">';
            echo '<a href="'. get_permalink() .'">';
            echo $product->get_image();
            echo '<h4 class="fp-title">'. $product->get_name() .'</h4>';
            echo '<p class="fp-price">'. $product->get_price_html() .'</p>';
            echo '</a>';
            echo '</div>';
        }

        echo '</div></div>';
    }

    wp_reset_postdata();
    return ob_get_clean();
});*/

add_shortcode('custom_categories_slider', function () {

    // SADECE ANASAYFADA GÖRÜNÜR
    if ( ! is_front_page() ) {
        return ''; // diğer sayfalarda tamamen boş döner
    }
	
	if ( wp_is_mobile() === false ) {
    return '';
	}
	
    ob_start();

    // BAŞLIK BURADA
    echo '<h2 class="fp-section-title" style="text-align:center; color:#92182C; margin-bottom:20px;">KATEGORİLER</h2>';

    // WooCommerce ürün kategorilerini çek
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
    ]);

    if (!empty($categories) && !is_wp_error($categories)) {

        echo '<div class="fp-wrapper">';
        echo '<button class="fp-prev">‹</button>';
        echo '<button class="fp-next">›</button>';
        echo '<div class="fp-track">';

        foreach ($categories as $category) {

            // Kategori görseli
            $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);

            if ($thumbnail_id) {
                $image_html = wp_get_attachment_image(
					$thumbnail_id,
					'woocommerce_single',
					false,
					['loading' => 'lazy']
				);
            } else {
                $image_html = function_exists('wc_placeholder_img')
                    ? wc_placeholder_img('woocommerce_thumbnail')
                    : '<img src="'. esc_url(wc_placeholder_img_src()) .'" alt="'. esc_attr($category->name) .'">';
            }

            $category_link = get_term_link($category);

            echo '<div class="fp-item">';
            echo '<a href="'. esc_url($category_link) .'">';
            echo $image_html;
            echo '<h4 class="fp-title">'. esc_html($category->name) .'</h4>';
            echo '<p class="fp-price">'. intval($category->count) .' ÜRÜN</p>';
            echo '</a>';
            echo '</div>';
        }

        echo '</div></div>'; // track + wrapper
    }

    return ob_get_clean();
});

add_action('woocommerce_checkout_process', function() {

    // Kontrol edilecek telefon alanları
    $phone_fields = [
        'alici_telefon'  => '"ALICI" Telefonu',
        'gonderen_telefon' => '"GÖNDEREN" Telefonu'
    ];

    foreach ($phone_fields as $field_key => $field_label) {

        if (!isset($_POST[$field_key])) continue;

        // Sadece rakamları al
        $phone = preg_replace('/\D/', '', $_POST[$field_key]);

        // Kurallar: 11 hane + 0 ile başlama
        if (strlen($phone) !== 11 || $phone[0] !== '0') {
            wc_add_notice(
                $field_label . ' 0 ile başlayan 11 haneli bir numara olmalıdır.',
                'error'
            );
        }
    }

});

add_shortcode('custom_featured_products_slider', function () {

    // SADECE ANASAYFADA GÖRÜNÜR
    if ( ! is_front_page() ) {
        return '';
    }

    ob_start();

    // Başlık
    echo '<h2 class="fp-section-title" style="text-align:center; color:#92182C; margin-bottom:0px;">ÖNE ÇIKANLAR</h2>';

    // Featured ürünleri çek
    $loop = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 12,
        'tax_query'      => [
            [
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
            ],
        ],
    ]);

    if ($loop->have_posts()) {

        // ✔ GRID YAPISI BAŞLIYOR
        echo '<div class="fp-grid">';

        while ($loop->have_posts()) {
            $loop->the_post();
            global $product;

            echo '<div class="fp-grid-item">';
            echo '<a href="'. esc_url(get_permalink()) .'">';
            echo wp_get_attachment_image(
				$product->get_image_id(),
				'woocommerce_single',
				false,
				['loading' => 'lazy']
			);
            echo '<h4 class="fp-title">'. esc_html($product->get_name()) .'</h4>';
            echo '<p class="fp-price">'. wp_kses_post($product->get_price_html()) .'</p>';
            echo '</a>';
            echo '</div>';
        }

        echo '</div>'; // grid kapanış
    }

    wp_reset_postdata();
    return ob_get_clean();
});

add_action('woocommerce_checkout_process', function () {

    $same_person = isset($_POST['same_person']);

    $sender_phone   = $_POST['gonderen_telefon'] ?? '';
    $receiver_phone = $_POST['alici_telefon'] ?? '';

    if (!$same_person) {
        if ($sender_phone && $receiver_phone && $sender_phone === $receiver_phone) {
            wc_add_notice(
                'Alıcı ve gönderen aynı kişi değil ise telefon numaraları farklı olmalıdır.',
                'error'
            );
        }
    }
});

add_action( 'woocommerce_before_cart_totals', function () {
    echo '<div class="delivery-fee-warning">
        <strong></strong> Seçeceğiniz gönderim adresine göre teslimat ücreti değişiklik gösterebilir.
    </div>';
});	

/* --------------------------------------------------------------------------
 * WHATSAPP SİPARİŞ BİLDİRİMİ
 * -------------------------------------------------------------------------- */

/* // ESKİ KOD (SİLME, YEDEK OLSUN)
add_action("woocommerce_checkout_order_processed", "send_order_to_webhook", 10, 1);
function send_order_to_webhook($order_id) {

    $order = wc_get_order($order_id);

    $data = array(
        "id"     => $order->get_id(),
        "total"  => $order->get_total(),
        "billing" => array(
            "first_name" => $order->get_meta('gonderen_isim'),
            "phone" => $order->get_meta('gonderen_telefon'),
        ),
    );

    wp_remote_post("https://flask-backend-751736088254.europe-west1.run.app/order_notification/", array(
        "method"  => "POST",
        "headers" => array("Content-Type" => "application/json"),
        "body"    => wp_json_encode($data),
        "timeout" => 60,
    ));
}*/

add_action("woocommerce_checkout_order_processed", "send_order_to_webhook", 10, 1);
function send_order_to_webhook($order_id) {

    $order = wc_get_order($order_id);

    // Gönderilen numarayı al (05xxxxxxxxx)
    $raw_phone = $order->get_meta('gonderen_telefon');

    // Tüm rakamları ayıkla
    $digits = preg_replace('/\D/', '', $raw_phone);

    // Eğer 05 ile başlıyorsa +90 formatına çevir
    if (substr($digits, 0, 2) === '05') {
        $phone = '+90' . substr($digits, 1);  // 05xxxxxxxxx → +905xxxxxxxxx
    } else {
        $phone = $raw_phone; // beklenmeyen format varsa orijinal kalsın
    }

    $data = array(
        "id"     => $order->get_id(),
        "total"  => $order->get_total(),
        "billing" => array(
            "first_name" => $order->get_meta('gonderen_isim'),
            "phone"      => $phone,   //+90 ile gönderiyoruz
        ),
    );

    wp_remote_post("webhook url", array(
        "method"  => "POST",
        "headers" => array("Content-Type" => "application/json"),
        "body"    => wp_json_encode($data),
        "timeout" => 60,
    ));
}
