<?php
/**
 * Plugin Name: GF WPML Validation Messages Fix

 * Description: Corrige los mensajes de validación de Gravity Forms que no se traducen
 *              correctamente con WPML. Compatible con cualquier idioma, sin necesidad
 *              de actualización manual al añadir nuevos idiomas.
 * Version: 2.0.0
 * Author: AMDT (Aún Más Difícil Todavía)
 * License: GPL-2.0+
 *
 * PROBLEMA:
 * Gravity Forms genera mensajes de validación por defecto con __('text', 'gravityforms').
 * WPML cambia el idioma dinámicamente, pero el textdomain de GF puede estar cargado
 * en el idioma incorrecto. Además, GFML solo traduce errorMessage personalizados de
 * cada campo, no los mensajes por defecto ("This field is required.", etc.).
 *
 * SOLUCIÓN (3 capas, de más a menos prioritaria):
 * 1. CAPA 1 - Recarga de textdomain: Fuerza la recarga del .mo de GF al idioma
 *    correcto cada vez que WPML cambia de idioma. Usa el filtro 'wpml_locale' de WPML
 *    para resolver automáticamente el locale correcto para cualquier idioma.
 * 2. CAPA 2 - Filtro gettext: Si tras la recarga el .mo no existe para ese idioma,
 *    busca la traducción en WPML String Translation (por si el admin la tradujo allí).
 * 3. CAPA 3 - Fallback hardcoded: Solo para los mensajes más críticos, como última
 *    red de seguridad si las capas 1 y 2 fallan.
 *
 * ESCALABILIDAD:
 * - Las capas 1 y 2 funcionan automáticamente para CUALQUIER idioma nuevo.
 * - La capa 3 solo cubre los mensajes más comunes como safety net.
 * - Si se añade un idioma nuevo y GF tiene .mo para él → funciona automático (capa 1).
 * - Si no tiene .mo pero el admin traduce en WPML ST → funciona automático (capa 2).
 * - Si ninguna de las dos → se muestra el mensaje en inglés (o el fallback si existe).
 *
 * INSTALACIÓN: Subir a wp-content/mu-plugins/ — se activa solo automáticamente.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GF_WPML_Validation_Fix {

    /**
     * Traducciones hardcoded de último recurso (capa 3).
     * Solo se usan si las capas 1 (.mo) y 2 (WPML ST) fallan.
     * Organizadas por locale de WordPress para máxima compatibilidad.
     *
     * NOTA: No necesitas editar esto al añadir idiomas nuevos.
     * Solo cubre casos donde no existe .mo Y no se ha traducido en WPML ST.
     */
    private static $emergency_fallback = [
        'This field is required.' => [
            'es' => 'Este campo es obligatorio.',
            'fr' => 'Ce champ est obligatoire.',
            'it' => 'Questo campo è obbligatorio.',
            'pt' => 'Este campo é obrigatório.',
            'pl' => 'To pole jest wymagane.',
            'tr' => 'Bu alan zorunludur.',
            'cs' => 'Toto pole je povinné.',
            'el' => 'Αυτό το πεδίο είναι υποχρεωτικό.',
            'ro' => 'Acest câmp este obligatoriu.',
            'de' => 'Dieses Feld ist erforderlich.',
            'nl' => 'Dit veld is verplicht.',
            'ja' => 'この項目は必須です。',
            'zh' => '此字段为必填项。',
            'ko' => '이 필드는 필수입니다.',
            'ar' => 'هذا الحقل مطلوب.',
            'ru' => 'Это поле обязательно для заполнения.',
            'sv' => 'Det här fältet är obligatoriskt.',
            'da' => 'Dette felt er påkrævet.',
            'nb' => 'Dette feltet er obligatorisk.',
            'fi' => 'Tämä kenttä on pakollinen.',
            'hu' => 'Ez a mező kötelező.',
            'bg' => 'Това поле е задължително.',
            'hr' => 'Ovo polje je obavezno.',
            'sk' => 'Toto pole je povinné.',
            'sl' => 'To polje je obvezno.',
            'uk' => 'Це поле є обов\'язковим.',
            'ca' => 'Aquest camp és obligatori.',
            'eu' => 'Eremu hau derrigorrezkoa da.',
            'gl' => 'Este campo é obrigatorio.',
            'he' => 'שדה זה הוא שדה חובה.',
            'hi' => 'यह फ़ील्ड आवश्यक है।',
            'th' => 'จำเป็นต้องกรอกข้อมูลในช่องนี้',
            'vi' => 'Trường này là bắt buộc.',
            'id' => 'Bidang ini wajib diisi.',
            'ms' => 'Ruangan ini wajib diisi.',
        ],
        'There was a problem with your submission.' => [
            'es' => 'Hubo un problema con tu envío.',
            'fr' => 'Un problème est survenu lors de votre envoi.',
            'it' => 'Si è verificato un problema con l\'invio.',
            'pt' => 'Houve um problema com o seu envio.',
            'pl' => 'Wystąpił problem z Twoim zgłoszeniem.',
            'tr' => 'Gönderiminizle ilgili bir sorun oluştu.',
            'cs' => 'Při odesílání formuláře došlo k problému.',
            'el' => 'Παρουσιάστηκε πρόβλημα με την υποβολή σας.',
            'ro' => 'A apărut o problemă cu trimiterea dvs.',
            'de' => 'Bei Ihrer Übermittlung ist ein Problem aufgetreten.',
            'nl' => 'Er is een probleem opgetreden bij het verzenden.',
            'ja' => '送信に問題がありました。',
            'ru' => 'При отправке возникла проблема.',
        ],
        'Please review the fields below.' => [
            'es' => 'Por favor, revisa los campos a continuación.',
            'fr' => 'Veuillez vérifier les champs ci-dessous.',
            'it' => 'Si prega di rivedere i campi sottostanti.',
            'pt' => 'Por favor, reveja os campos abaixo.',
            'pl' => 'Proszę sprawdzić poniższe pola.',
            'tr' => 'Lütfen aşağıdaki alanları gözden geçirin.',
            'cs' => 'Zkontrolujte prosím níže uvedená pole.',
            'el' => 'Παρακαλώ ελέγξτε τα παρακάτω πεδία.',
            'ro' => 'Vă rugăm să verificați câmpurile de mai jos.',
            'de' => 'Bitte überprüfen Sie die untenstehenden Felder.',
            'nl' => 'Controleer de onderstaande velden.',
            'ja' => '以下のフィールドを確認してください。',
            'ru' => 'Пожалуйста, проверьте поля ниже.',
        ],
        'Please enter a valid email address.' => [
            'es' => 'Por favor, introduce una dirección de email válida.',
            'fr' => 'Veuillez entrer une adresse e-mail valide.',
            'it' => 'Inserisci un indirizzo email valido.',
            'pt' => 'Por favor, insira um endereço de email válido.',
            'pl' => 'Proszę podać prawidłowy adres e-mail.',
            'tr' => 'Lütfen geçerli bir e-posta adresi girin.',
            'cs' => 'Zadejte prosím platnou e-mailovou adresu.',
            'el' => 'Παρακαλώ εισάγετε μια έγκυρη διεύθυνση email.',
            'ro' => 'Vă rugăm să introduceți o adresă de email validă.',
            'de' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.',
            'nl' => 'Voer een geldig e-mailadres in.',
            'ja' => '有効なメールアドレスを入力してください。',
            'ru' => 'Пожалуйста, введите действительный адрес электронной почты.',
        ],
    ];

    /**
     * Caché interna para evitar múltiples llamadas a WPML por request.
     */
    private static $current_lang_cache = null;
    private static $current_locale_cache = null;

    /**
     * Inicializa los hooks.
     */
    public static function init() {
        add_action( 'plugins_loaded', [ __CLASS__, 'register_hooks' ], 5 );
    }

    /**
     * Registra hooks solo si WPML y GF están activos.
     */
    public static function register_hooks() {
        if ( ! defined( 'ICL_SITEPRESS_VERSION' ) || ! class_exists( 'GFForms' ) ) {
            return;
        }

        // Capa 1: Recarga de textdomain al cambiar de idioma.
        add_action( 'wpml_language_has_switched', [ __CLASS__, 'reload_gf_textdomain' ] );

        // Asegurar recarga también en frontend antes del render del formulario.
        add_filter( 'gform_pre_render', [ __CLASS__, 'ensure_textdomain_loaded' ], 1 );
        add_filter( 'gform_pre_process', [ __CLASS__, 'ensure_textdomain_loaded' ], 1 );

        // Capa 2 + 3: Filtro gettext como red de seguridad.
        add_filter( 'gettext', [ __CLASS__, 'filter_gettext' ], 20, 3 );

        // Red adicional: filtros específicos de GF.
        add_filter( 'gform_field_validation', [ __CLASS__, 'translate_field_validation' ], 25, 4 );
        add_filter( 'gform_validation_message', [ __CLASS__, 'translate_validation_message' ], 20, 2 );

        // Limpiar caché al cambiar de idioma.
        add_action( 'wpml_language_has_switched', [ __CLASS__, 'clear_cache' ], 1 );
    }

    /**
     * Limpia la caché interna.
     */
    public static function clear_cache() {
        self::$current_lang_cache   = null;
        self::$current_locale_cache = null;
    }

    /**
     * Obtiene el código de idioma WPML actual (con caché).
     *
     * @return string|false Código de idioma (ej: 'es', 'fr', 'es-cl') o false.
     */
    private static function get_current_language() {
        if ( self::$current_lang_cache !== null ) {
            return self::$current_lang_cache;
        }
        self::$current_lang_cache = function_exists( 'icl_get_current_language' )
            ? icl_get_current_language()
            : false;
        return self::$current_lang_cache;
    }

    /**
     * Obtiene el locale de WP correspondiente al idioma WPML actual.
     * Usa el filtro 'wpml_locale' de WPML, que funciona para CUALQUIER idioma
     * configurado, sin necesidad de mantener un mapa manual.
     *
     * @return string|false Locale (ej: 'es_ES', 'fr_FR') o false.
     */
    private static function get_current_locale() {
        if ( self::$current_locale_cache !== null ) {
            return self::$current_locale_cache;
        }

        $lang = self::get_current_language();
        if ( ! $lang || $lang === 'en' ) {
            self::$current_locale_cache = false;
            return false;
        }

        // WPML proporciona este filtro que resuelve automáticamente el locale
        // correcto para cualquier idioma configurado en el sitio.
        self::$current_locale_cache = apply_filters( 'wpml_locale', $lang );
        return self::$current_locale_cache;
    }

    /**
     * Extrae el código base de idioma (2 letras) de un locale o código WPML.
     * Ejemplos: 'es_ES' → 'es', 'es-cl' → 'es', 'pt_PT' → 'pt'
     *
     * @param string $code Locale o código WPML.
     * @return string Código de 2 letras.
     */
    private static function get_base_language( $code ) {
        // Para locales tipo es_ES, pt_BR, etc.
        if ( strpos( $code, '_' ) !== false ) {
            return strtolower( substr( $code, 0, 2 ) );
        }
        // Para códigos WPML tipo es-cl, es-mx, etc.
        if ( strpos( $code, '-' ) !== false ) {
            return strtolower( substr( $code, 0, 2 ) );
        }
        return strtolower( $code );
    }

    /**
     * CAPA 1: Fuerza la recarga del textdomain de GF al idioma correcto.
     * Usa 'wpml_locale' para resolver automáticamente el locale — funciona
     * con cualquier idioma que WPML tenga configurado.
     */
    public static function reload_gf_textdomain() {
        $locale = self::get_current_locale();
        if ( ! $locale ) {
            return;
        }

        // Descargar el textdomain actual.
        unload_textdomain( 'gravityforms' );

        // Intentar cargar desde wp-content/languages/plugins/ (ubicación estándar).
        $mo_file = WP_LANG_DIR . '/plugins/gravityforms-' . $locale . '.mo';
        if ( file_exists( $mo_file ) ) {
            load_textdomain( 'gravityforms', $mo_file );
            return;
        }

        // Intentar con el código base (ej: para es_CL intentar con es_ES).
        $base_lang = self::get_base_language( $locale );
        $fallback_locales = [
            'es' => 'es_ES',
            'pt' => 'pt_PT',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
            'nl' => 'nl_NL',
            'zh' => 'zh_CN',
        ];
        if ( isset( $fallback_locales[ $base_lang ] ) && $fallback_locales[ $base_lang ] !== $locale ) {
            $fallback_mo = WP_LANG_DIR . '/plugins/gravityforms-' . $fallback_locales[ $base_lang ] . '.mo';
            if ( file_exists( $fallback_mo ) ) {
                load_textdomain( 'gravityforms', $fallback_mo );
                return;
            }
        }

        // Último intento: directorio del plugin.
        $plugin_mo = WP_PLUGIN_DIR . '/gravityforms/languages/gravityforms-' . $locale . '.mo';
        if ( file_exists( $plugin_mo ) ) {
            load_textdomain( 'gravityforms', $plugin_mo );
        }
    }

    /**
     * Asegura que el textdomain esté cargado correctamente antes del render/procesado.
     * Enganchado a gform_pre_render y gform_pre_process con prioridad 1.
     *
     * @param array $form Datos del formulario.
     * @return array Formulario sin modificar.
     */
    public static function ensure_textdomain_loaded( $form ) {
        static $loaded = false;
        if ( ! $loaded ) {
            self::reload_gf_textdomain();
            $loaded = true;
        }
        return $form;
    }

    /**
     * Busca un fallback para un mensaje en inglés.
     * Intenta primero con el código WPML exacto, luego con el código base.
     *
     * @param string $english_text Mensaje original en inglés.
     * @return string|false Traducción o false si no se encuentra.
     */
    private static function find_fallback( $english_text ) {
        if ( ! isset( self::$emergency_fallback[ $english_text ] ) ) {
            return false;
        }

        $lang = self::get_current_language();
        $translations = self::$emergency_fallback[ $english_text ];

        // Intento 1: código WPML exacto (ej: 'es', 'fr', 'es-cl').
        if ( isset( $translations[ $lang ] ) ) {
            return $translations[ $lang ];
        }

        // Intento 2: código base (ej: 'es-cl' → 'es', 'pt-br' → 'pt').
        $base = self::get_base_language( $lang );
        if ( isset( $translations[ $base ] ) ) {
            return $translations[ $base ];
        }

        return false;
    }

    /**
     * CAPA 2 + 3: Filtro gettext como red de seguridad.
     *
     * Si la capa 1 (.mo) no logró traducir el string (porque no existe .mo
     * para ese idioma), intenta:
     *   - Capa 2: Buscar en WPML String Translation (por si el admin lo tradujo allí).
     *   - Capa 3: Usar el fallback hardcoded como último recurso.
     */
    public static function filter_gettext( $translation, $text, $domain ) {
        // Solo actuar en el dominio de Gravity Forms.
        if ( $domain !== 'gravityforms' ) {
            return $translation;
        }

        // Si ya se tradujo correctamente (el .mo funcionó), no hacer nada.
        if ( $translation !== $text ) {
            return $translation;
        }

        $lang = self::get_current_language();
        if ( ! $lang || $lang === 'en' ) {
            return $translation;
        }

        // Capa 2: Intentar con WPML String Translation.
        // Registra el string si no existe, y busca la traducción.
        if ( function_exists( 'icl_t' ) ) {
            $st_context = 'gravityforms';
            $translated = apply_filters( 'wpml_translate_single_string', $text, $st_context, $text );
            if ( $translated !== $text ) {
                return $translated;
            }
        }

        // Capa 3: Fallback hardcoded.
        $fallback = self::find_fallback( $text );
        if ( $fallback ) {
            return $fallback;
        }

        return $translation;
    }

    /**
     * Filtro de validación de campo como red adicional.
     * Actúa DESPUÉS del filtro de GFML (prioridad 10).
     */
    public static function translate_field_validation( $result, $value, $form, $field ) {
        if ( $result['is_valid'] ) {
            return $result;
        }

        $lang = self::get_current_language();
        if ( ! $lang || $lang === 'en' ) {
            return $result;
        }

        // Si el mensaje sigue en inglés, buscar fallback.
        $fallback = self::find_fallback( $result['message'] );
        if ( $fallback ) {
            $result['message'] = $fallback;
        }

        return $result;
    }

    /**
     * Traduce el mensaje global de error del formulario (el H2 rojo de arriba).
     */
    public static function translate_validation_message( $message, $form ) {
        $lang = self::get_current_language();
        if ( ! $lang || $lang === 'en' ) {
            return $message;
        }

        foreach ( self::$emergency_fallback as $en_text => $translations ) {
            $fallback = self::find_fallback( $en_text );
            if ( $fallback && strpos( $message, $en_text ) !== false ) {
                $message = str_replace( $en_text, $fallback, $message );
            }
        }

        return $message;
    }
}

GF_WPML_Validation_Fix::init();
