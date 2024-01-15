<?php

/*****************************************************************************
 * DigiMember Api
 *
 * For an example test/demo plugin, visit: https://digimember.de/api
 *****************************************************************************/


/*
 * ----------------------------------------------------------------------------
 * digimember_ApiVersion()
 * ----------------------------------------------------------------------------
 *
 * Returns the current version of the api.
 *
 * Example:
 *
 *    $version = digimember_ApiVersion();
 *
 *    var_dump( $version );
 *
 * Output:
 *
 * float(1.20)
 *
 */
function digimember_ApiVersion() {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getCourseProgress( $product_id='current' )
 * ----------------------------------------------------------------------------
 */
function digimember_getCourseProgress( $product_id='current' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getLectureMenu( $product_id='current' )
 * ----------------------------------------------------------------------------
 */
function digimember_getLectureMenu( $product_id='current' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getLectureMenu( $product_id='current' )
 *
 * Output:
 *
 * array( $start_of_course, $prev_module, $prev_lecture, $next_lecture, $next_module, $end_of_course )
 *
 * $start_of_course = Link rec to first lecture of course
 * $prev_module = false (is course has no or one modules) or link rec to first lecture of previous module
 * $prev_lecture = link rec to previous module
 * $next_lecture = link rec to next module
 * $next_module = false (if course has no or one moduules) or link rec to firstl ecture of next module
 * $end_of_course = link rect to last lecture of course
 *
 *
 * A link rec is an associative array with these keys:
 * url - the target url for the link or false (if the link is disabled)
 * label - the readable text of the link
 * description - a one sentence explanation of the links - good for tool tips
 *
 * ----------------------------------------------------------------------------
 */
function digimember_getLectureNavLinks( $product_id='current' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listProducts()
 * ----------------------------------------------------------------------------
 *
 * List all active products.
 *
 * Example:
 *
 *    $products = digimember_listProducts();
 *
 *    print_r( $products );
 *
 * Output:
 *
 * Array
 * (
 *     [0] => stdClass Object
 *         (
 *             [id] => 8
 *             [created] => 2012-12-02 13:48:47
 *             [name] => Some Product Name
 *             [properties] => array()
 *         )
 *
 *     [1] => stdClass Object
 *         (
 *             [id] => 4
 *             [created] => 2012-10-16 17:27:43
 *             [name] => Another Product Name
 *             [properties] => array()
 *         )
 *  )
 */
function digimember_listProducts() {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listAccessMetaOfContent( $content_id, $content_type='all' )
 * ----------------------------------------------------------------------------
 *
 * List all products assigned to a page or pst.
 *
 * Example:
 *
 *    $products = digimember_listProductsOfContent( 3 );
 *
 *    print_r( $products );
 *
 * Output:
 *
 * Array
 * (
 *     [0] => stdClass Object
 *         (
 *             [content_id] => 2628
 *             [content_type] => page
 *             [product_id] => 4
 *             [unlock_day] => 0
 *         )
 *
 *     [1] => stdClass Object
 *         (
 *             [content_id] => 2628
 *             [content_type] => page
 *             [product_id] => 6
 *             [unlock_day] => 7
 *         )
 * )
 *
 */
function digimember_listAccessMetaOfContent( $content_id, $content_type='all' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listAccessableProducts( $wordpress_user_id='current' )
 * ----------------------------------------------------------------------------
 *
 * List all protected content of a certain type (page, post, or a custom post type) a user can access
 *
 * Example:
 *
 *    $content = digimember_listAccessableContent( 'page', 'current' );
 *
 *    print_r( $content );
 *
 * Output:
 * Array
 * (
 *     [0] => Array
 *         (
 *             [id] => 1
 *             [created] => 2015-01-22 12:14:11
 *             [name] => Basic training
 *             [first_login_url] =>
 *             [default_login_url] =>
 *             [shortcode_url] =>
 *             [properties] => Array
 *                 (
 *                 )
 *
 *         )
 *
 *     [1] => Array
 *         (
 *             [id] => 1
 *             [created] => 2015-01-22 12:14:11
 *             [name] => Advanced training
 *             [first_login_url] =>
 *             [default_login_url] =>
 *             [shortcode_url] =>
 *             [properties] => Array
 *                 (
 *                 )
 *
 *         )
 */
function digimember_listAccessableProducts( $wordpress_user_id='current' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listAccessableContent( $content_type, $wordpress_user_id='current' )
 * ----------------------------------------------------------------------------
 *
 * List all protected content of a certain type (page, post, or a custom post type) a user can access
 *
 * Example:
 *
 *    $content = digimember_listAccessableContent( 'page', 'current' );
 *
 *    print_r( $content );
 *
 * Output:
 * Array
 * (
 *     [0] => Array
 *         (
 *             [product_id] => 123
 *             [product_name] => Basic training
 *             [posts] => Array
 *                 (
 *                     [0] => Array
 *                         (
 *                             [content_id] => 45
 *                             [title] => Lecture 1
 *                             [unlock_day] => 0
 *                             [position] => 1
 *                         )
 *
 *                     [1] => Array
 *                         (
 *                             [content_id] => 49
 *                             [title] => Lectur2
 *                             [unlock_day] => 0
 *                             [position] => 2
 *                         )
 *
 *                 )
 *
 *         )
 *
 *     [1] => Array
 *         (
 *             [product_id] => 456
 *             [product_name] => Advanced training
 *             [posts] => Array
 *                 (
 *                     [0] => Array
 *                         (
 *                             [content_id] => 15
 *                             [title] => Introduction
 *                             [unlock_day] => 0
 *                             [position] => 1
 *                         )
 *
 *                 )
 *
 *         )
 * )
 */
function digimember_listAccessableContent( $content_type, $wordpress_user_id='current' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getProduct( $product_id )
 * ----------------------------------------------------------------------------
 *
 * Get one product
 *
 * Example:
 *
 *    $product = digimember_getProduct( 1 );
 *
 *    print_r( $product );
 *
 * Output:
 *
 * Array
 * (
 *     stdClass Object
 *     (
 *          [id] => 8
 *          [created] => 2012-12-02 13:48:47
 *          [name] => Some Product Name
 *          [properties] => array()
 *     )
 *
 *  )
 */
function digimember_getProduct( $product_id ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listAccessMetaOfProduct( $product_id )
 * ----------------------------------------------------------------------------
 *
 * List all content assigned to a product.
 *
 * Example:
 *
 *    $content = digimember_listAccessMetaOfProduct( 7);
 *
 *    print_r( $content );
 *
 * Output:
 *
 * Array
 * (
 *     [0] => stdClass Object
 *         (
 *             [content_id] => 1              // page resp. post id
 *             [content_type] => page         // content type: "page" or "post"
 *             [product_id] => 9
 *             [unlock_day] => 0              // number of days after the
 *                                            // 0 = unlocked directly after
 *                                            // purchase
 *         )
 *
 *     [1] => stdClass Object
 *         (
 *             [content_id] => 2
 *             [content_type] => page
 *             [product_id] => 8
 *             [unlock_day] => 7              // unlock after 7 days
 *         )
 *
 *     [2] => stdClass Object
 *         (
 *             [content_id] => 3
 *             [content_type] => 'post'
 *             [product_id] => 8
 *             [unlock_day] => 14             // unlock after 14 days
 *         )
 * )
 *
 */
function digimember_listAccessMetaOfProduct( $product_id ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listOrders( $wordpress_user_id )
 * ----------------------------------------------------------------------------
 *
 * List all active orders of a user.
 *
 * Example:
 *
 *    $wordpress_user_id = 1;
 *
 *    $orders = digimember_listOrders( $wordpress_user_id );
 *
 *    print_r( $orders );
 *
 * Output:
 *
 * Array
 * (
 *     [0] => stdClass Object
 *         (
 *             [user_id] => 1
 *             [product_id] => 8
 *             [order_id] => d9w090bcms58
 *             [created] => 2012-10-15 22:23:00
 *             [age_in_days] => 137
 *         )
 *
 *     [1] => stdClass Object
 *         (
 *             [user_id] => 1
 *             [product_id] => 4
 *             [order_id] => a3e2b56sbei7
 *             [created] => 2013-02-28 16:21:18
 *             [age_in_days] => 12
 *         )
 *
 * )
 */
function digimember_listOrders( $wordpress_user_id='current' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getOrder( $order_id )
 * ----------------------------------------------------------------------------
 *
 * Returns order data of order with id $order_id.
 *
 * Returns false, if no order for $order_id is found.
 *
 * Example:
 *
 *    $order_id = 'a3e2b56sbei7';
 *
 *    $order = digimember_getOrder( $order_id );
 *
 *    print_r( $order );
 *
 * Output:
 *
 * stdClass Object
 *     (
 *         [user_id] => 1
 *         [product_id] => 8
 *         [order_id] => d9w090bcms58
 *         [created] => 2012-10-15 22:23:00
 *         [age_in_days] => 137
 *     )
 *
 */
function digimember_getOrder( $order_id ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_createOrder( $product_id_or_ids, $email, $first_name='', $last_name='', $order_id='', $do_perform_login=false  )
 * ----------------------------------------------------------------------------
 *
 * Creates an order and a user account (if the user has no account).
 *
 * Parameters:
 *
 * $user_id: the Wordpres user id
 *
 * $product_id_or_ids    product id or list of product ids - either as array or
 *                       a string list (sepeated comma, semiclon or blanks)
 *
 * $email                User data of the account to be created with create the
 *                       order
 * $first_name
 * $last_name
 *
 * $order_id
 *
 * $do_perform_login     If true, the user will be logged in - use this e.g. for
 *                       sign up forms.
 *
 * Example:
 *
 *    digimember_createOrder( 20,
 *                           'test@email.de',
 *                           'Claus',
 *                           'Myers',
 *                           'Order-1' );
 *
 *    digimember_createOrder( array( 20, 21, 22),
 *                           'test@email.de',
 *                           'Claus',
 *                           'Myers',
 *                           'Order-1' );
 *
 *    digimember_createOrder( '20, 21, 22',
 *                           'test@email.de',
 *                           'Claus',
 *                           'Myers',
 *                           'Order-1' );
 *
 */
function digimember_createOrder( $product_id_or_ids, $email, $first_name='', $last_name='', $order_id='', $do_perform_login=false  ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_listContentTypes()
 * ----------------------------------------------------------------------------
 *
 * List all types of content handled by DigiMember
 *
 * Example:
 *
 *    $types = digimember_listContentTypes();
 *
 *    print_r( $types );
 *
 * Output:
 *
 * Array
 * (
 *     [0] => page
 *     [1] => post
 * )
 */
function digimember_listContentTypes() {}

/*
 * ----------------------------------------------------------------------------
 * digimember_userAccessDenied( $user_id, $content_type, $content_id )
 * ----------------------------------------------------------------------------
 *
 * Check if the access to a piece of content is blocked for the given
 * user.
 *
 * Parameters:
 *
 * $user_id: the Wordpres user id
 *
 * $content_type: "page", "post" (or - if the api is extended - any other
 *                content type returned by digimember_listContentTypes())
 *
 * $content_id: id of the wordpress page or post
 *
 * Returns:
 *    false: if the content is not locked (i.e.: the content is accessable)
 *    true:  if the content is locked and will not automatically be unlocked.
 *    int:   if the content will be automatically unlocked in the future:
 *           the number of days, the user has to wait.
 *
 * Example:
 *
 *    $result = digimember_currentUserAccessDenied( 'post', 123 );
 *
 *    var_dump( $result );
 *
 * Output:
 *
 *  bool(false)  // the content is accessable
 *
 *  or
 *
 *  bool(true)   // the content is locked and will not be automatically unlocked.
 *
 *  or
 *
 *  int(17)      // the content will be unlocked in 17 days
 *
 */
function digimember_userAccessDenied( $user_id, $content_type, $content_id ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_currentUserAccessDenied( $content_type, $content_id )
 * ----------------------------------------------------------------------------
 *
 * Check if the access to a piece of content is blocked for the current
 * session user.
 *
 * Works exaclty like digimember_userAccessDenied(), but for the current
 * session user.
 *
 */
function digimember_currentUserAccessDenied( $content_type, $content_id ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_registerProductProperty( $property_name,
 *                                     $property_type,
 *                                     $label,
 *                                     $meta=array() )
 * ----------------------------------------------------------------------------
 *
 * Adds a property to every product. The property can be edited in the product
 * editor (in the wordpress admin area on page DigiMember - Products).
 *
 * The properties are stored in the product objects field 'properties' in form
 * of an associative array.
 *
 * See digimember_listProducts() and digimember_getProduct()
 *
 * Parameters:
 *
 * $property_name may container letters, digits and underscores. Must be unique.
 * If you call digimember_registerProductProperty() for the same property name
 * again, the previously set property settings are overwritten.
 *
 * $property type is a string with one of these values:
 *    "string"   one line text
 *    "text"     multi line text
 *    "html"
 *    "int"
 *    "bool"     true or false.
 *    "date"     e.g. "2014-01-23" (without time)
 *               or "2014-01-23 17:53:47" (with time)
 *    "array"
 *
 * $meta is an associative array with these (optional) keys and values:
 *
 * all types:
 *   section   - add this property to the for section registered via
 *               digimemberRegisterSection()
 *   default   - sets the initial value of the property
 *   tooltip   - a text shown as tooltip. Use pipe character | to seperate paragraphs,
 *               e.g. "This is the first paragraph.|This is the second."
 *   hint      - a text shown as small hint below the input field
 *
 * for string and int only:
 *   size      - size of the input field.
 *
 * for text only:
 *    cols
 *    rows     - size of text area input. Default is 40 cols and 5 rows.
 *
 * for html only:
 *    rows            - height of the html input area. Default is 5 rows.
 *    with_shortcodes - set this to true to display the DigiMember shortcode button.
 *                      Default is false.
 *
 * for array only:
 *    array     - associative array (value => label) with options to select from.
 *                See example.
 *
 * for date only:
 *    with_time - set this to true to also enter a time. Default is false.
 *
 *
 *
 * Example:
 *
 * function registerMyProductPropierties()
 * {
 *   $meta = array(
 *            'default' => 1,
 *            'tooltip' => 'This is an explanation for the number of licenses'
 *                       . ' property.|'
 *                       . 'To the user, this text is displayed as a tooltip.'
 *           );
 *
 *   digimember_registerProductProperty( 'number_of_licenses',
 *                                       'int',
 *                                       'Number of licenses',
 *                                       $meta );
 *
 *
 *   $meta = array(
 *            'rows' => 10,
 *            'cols' => 60,
 *           );
 *
 *   digimember_registerProductProperty( 'description',
 *                                       'text',
 *                                       'Description',
 *                                       $meta );
 *
 *
 *
 *   $options = array( 'creme'           => 'with creme',
 *                     'sugar'           => 'with sugar',
 *                     'creme_and_sugar' => 'with creme and sugar',
 *                     'black'           => 'black',
 *              );
 *
 *   $meta = array(
 *            'array' => $options,
 *            'default' => 'creme_and_sugar',
 *           );
 *
 *   digimember_registerProductProperty( 'coffee_preference',
 *                                       'array',
 *                                       'How do you like your coffee?',
 *                                       $meta );
 * }
 *
 * add_action( 'plugins_loaded', 'registerMyProductPropierties' );
 *
 * // Goto to the Wordpress admin area, open page DigiMember - Products and edit a product!
 *
 */
function digimember_registerProductProperty( $property_name, $property_type, $label, $meta=array() ) {}

/*
 * ----------------------------------------------------------------------------
 * digimemberRegisterSection( $section_key, $headline, $instructions )
 * ----------------------------------------------------------------------------
 * Registers a section for the product edit form. You may then add
 * Properties to this section. Properties may also be added without section.
 *
 * Sections only affect how the properties are displayed on the product
 * edit form. They do _NOT_ affected how properties are accessed or handled.
 *
 * Paramters
 *   $section_key    a unique key containing only numbers, letters and underscores
 *   $headline       the label displayed to the user (as a section headline)
 *   $instructions   optional html text displayed right after the section headline
 *
 */
function digimemberRegisterSection( $section_key, $headline, $instructions='' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_clearPropertiesOfProduct( $product_id )
 * ----------------------------------------------------------------------------
 * Product properties are never deleted, but hidden, when not registered
 * by digimember_registerProductProperty() (or when your plugin is disabled).
 *
 * To remove all properties from a single product from the database, use this function.
 */
function digimember_clearPropertiesOfProduct( $product_id ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getAutoresponderOptions( $addNullEntryLabel=false )
 * ----------------------------------------------------------------------------
 * Returns a list of autoresponder settings as setup under DigiMember - Autoresponder
 *
 * Example:
 *
 *    $options = digimember_getAutoresponderOptions();
 *
 *    print_r( $options );
 *
 * Output:
 *
 * Array
 * (
 *     [3] => 3 - AWeber
 *     [7] => 7 - Getresponse
 *     [1] => 1 - KlickTipp
 * )
 */
function digimember_getAutoresponderOptions( $addNullEntryLabel=false ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_AutoresponderSubscribe( $autoresponder_id, $email, $first_name, $last_name )
 * ----------------------------------------------------------------------------
 * Subscribes a user to an autoresponder.
 *
 * Parameters:
 *
 * $autoresponder_id: The id of the autoresponder as given by digimember_getAutoresponderOptions().
 *
 */
function digimember_AutoresponderSubscribe( $autoresponder_id, $email, $first_name='', $last_name='', $product_id=0, $order_id='' ) {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getDs24AffiliateName()
 * ----------------------------------------------------------------------------
 * Retrieve the Digistore24 affiliate name of the current user (if the user
 * was sent by Digistore24 either as a customer or as an affiliate).
 *
 * If sent as a customer and not an affiliate, the affiliate name is the
 * Digistore24 affiliate name the user would get, if he follows the Digistore24
 * "become affiliate" link (see shortcode [ds_buyer_to_affiliate] and
 * digimember_getDs24BuyerToAffiliateUrl() ).
 *
 * If no Digistore order is found, it returns ''. Maximum length of the
 * Digistore24 id is 47 characters.
 *
 */
function digimember_getDs24AffiliateName() {}

/*
 * ----------------------------------------------------------------------------
 * digimember_getDs24BuyerToAffiliateUrl()
 * ----------------------------------------------------------------------------
 * Create an url for affiliation. If the current user follows this url,
 * he may take part in your "Buyer to affiliate" programm (which you need
 * to setup in Digistore24).
 *
 * There is difference between this function and the shortcode [ds_buyer_to_affiliate],
 * which only has consequences, if the user has multiple orders:
 *
 * The shortcodes scans all orders of the user for the buyer to affiliate
 * programm. This function only returns the url for the affiliate name
 * returned by digimember_getDs24AffiliateName(). To solve this, setup
 * the buyer to affiliate programm for all of your Digistore24 products the
 * user may access in this DigiMember installation.
 *
 * If no Digistore order is found, it returns ''.
 */
function digimember_getDs24BuyerToAffiliateUrl() {}

/*
 * ----------------------------------------------------------------------------
 * digimember_disableAffiliateFooterLink()
 * ----------------------------------------------------------------------------
 * The affiliate footer link is disabled for the current page view.
 *
 */
function digimember_disableAffiliateFooterLink() {}

/*
 * ----------------------------------------------------------------------------
 * digimember_disableAffiliateFooterLink()
 * ----------------------------------------------------------------------------
 * Returns true, if the admin has enabled the affiliate footer link.
 *
 */
function digimember_isAffiliateFooterLinkEnabled() {}

/*
 * ----------------------------------------------------------------------------
 * ----------------------------------------------------------------------------
 *
 * Usage:
 *
 * function digimember_someDeprecatedFunction()
 * {
 *
 *    return digimember_someNewFunction();
 *
 * }
 */
