<?php

if ( !defined( 'WPINC' ) ) {
  die;
}

/**
 * Class containing misc functions
 *
 * @since      1.0.0
 * @package    Activities
 * @subpackage Activities/includes
 * @author     Mikal Naustdal <miknau94@gmail.com>
 */
class Activities_Utility {
  /**
   * Gets full name of a user, or their display name.
   * Can add email to make it easier to indentify users with the same name.
   *
   * @param   int|WP_User   $user user_id or WP_User object
   * @param   bool          $email True to display email in parentices
   * @return  string        User name to display
   */
  static function get_user_name( $user, $email = true ) {
    if ( is_numeric( $user) ) {
      $user = new WP_User( $user );
    }
    $name = '';
    if ( $user->first_name != '' ) {
      $name .= $user->first_name . ' ';
    }
    if ( $user->last_name != '' ) {
      if ( $name != '') {
        $name .= ' ';
      }
      $name .= $user->last_name;
    }
    if ( $name == '' ) {
      $name = $user->get( 'display_name' );
    }

    if ( $email ) {
      $name .= sprintf( ' (%s)', $user->get( 'user_email' ) );
    }

    return $name;
  }

  /**
   * Loads mapping of countries
   *
   * @return array Country codes mapped to translated country names
   */
  static function get_countries() {
    return array(
      "AF" => esc_html__( "Afghanistan", 'activities' ),
      "AL" => esc_html__( "Albania", 'activities' ),
      "DZ" => esc_html__( "Algeria", 'activities' ),
      "AS" => esc_html__( "American Samoa", 'activities' ),
      "AD" => esc_html__( "Andorra", 'activities' ),
      "AO" => esc_html__( "Angola", 'activities' ),
      "AI" => esc_html__( "Anguilla", 'activities' ),
      "AQ" => esc_html__( "Antarctica", 'activities' ),
      "AG" => esc_html__( "Antigua and Barbuda", 'activities' ),
      "AR" => esc_html__( "Argentina", 'activities' ),
      "AM" => esc_html__( "Armenia", 'activities' ),
      "AW" => esc_html__( "Aruba", 'activities' ),
      "AU" => esc_html__( "Australia", 'activities' ),
      "AT" => esc_html__( "Austria", 'activities' ),
      "AZ" => esc_html__( "Azerbaijan", 'activities' ),
      "BS" => esc_html__( "Bahamas", 'activities' ),
      "BH" => esc_html__( "Bahrain", 'activities' ),
      "BD" => esc_html__( "Bangladesh", 'activities' ),
      "BB" => esc_html__( "Barbados", 'activities' ),
      "BY" => esc_html__( "Belarus", 'activities' ),
      "BE" => esc_html__( "Belgium", 'activities' ),
      "BZ" => esc_html__( "Belize", 'activities' ),
      "BJ" => esc_html__( "Benin", 'activities' ),
      "BM" => esc_html__( "Bermuda", 'activities' ),
      "BT" => esc_html__( "Bhutan", 'activities' ),
      "BO" => esc_html__( "Bolivia", 'activities' ),
      "BA" => esc_html__( "Bosnia and Herzegovina", 'activities' ),
      "BW" => esc_html__( "Botswana", 'activities' ),
      "BV" => esc_html__( "Bouvet Island", 'activities' ),
      "BR" => esc_html__( "Brazil", 'activities' ),
      "IO" => esc_html__( "British Indian Ocean Territory", 'activities' ),
      "BN" => esc_html__( "Brunei Darussalam", 'activities' ),
      "BG" => esc_html__( "Bulgaria", 'activities' ),
      "BF" => esc_html__( "Burkina Faso", 'activities' ),
      "BI" => esc_html__( "Burundi", 'activities' ),
      "KH" => esc_html__( "Cambodia", 'activities' ),
      "CM" => esc_html__( "Cameroon", 'activities' ),
      "CA" => esc_html__( "Canada", 'activities' ),
      "CV" => esc_html__( "Cape Verde", 'activities' ),
      "KY" => esc_html__( "Cayman Islands", 'activities' ),
      "CF" => esc_html__( "Central African Republic", 'activities' ),
      "TD" => esc_html__( "Chad", 'activities' ),
      "CL" => esc_html__( "Chile", 'activities' ),
      "CN" => esc_html__( "China", 'activities' ),
      "CX" => esc_html__( "Christmas Island", 'activities' ),
      "CC" => esc_html__( "Cocos (Keeling) Islands", 'activities' ),
      "CO" => esc_html__( "Colombia", 'activities' ),
      "KM" => esc_html__( "Comoros", 'activities' ),
      "CG" => esc_html__( "Congo", 'activities' ),
      "CD" => esc_html__( "Congo, the Democratic Republic of the", 'activities' ),
      "CK" => esc_html__( "Cook Islands", 'activities' ),
      "CR" => esc_html__( "Costa Rica", 'activities' ),
      "CI" => esc_html__( "Cote D'Ivoire", 'activities' ),
      "HR" => esc_html__( "Croatia", 'activities' ),
      "CU" => esc_html__( "Cuba", 'activities' ),
      "CY" => esc_html__( "Cyprus", 'activities' ),
      "CZ" => esc_html__( "Czech Republic", 'activities' ),
      "DK" => esc_html__( "Denmark", 'activities' ),
      "DJ" => esc_html__( "Djibouti", 'activities' ),
      "DM" => esc_html__( "Dominica", 'activities' ),
      "DO" => esc_html__( "Dominican Republic", 'activities' ),
      "EC" => esc_html__( "Ecuador", 'activities' ),
      "EG" => esc_html__( "Egypt", 'activities' ),
      "SV" => esc_html__( "El Salvador", 'activities' ),
      "GQ" => esc_html__( "Equatorial Guinea", 'activities' ),
      "ER" => esc_html__( "Eritrea", 'activities' ),
      "EE" => esc_html__( "Estonia", 'activities' ),
      "ET" => esc_html__( "Ethiopia", 'activities' ),
      "FK" => esc_html__( "Falkland Islands (Malvinas)", 'activities' ),
      "FO" => esc_html__( "Faroe Islands", 'activities' ),
      "FJ" => esc_html__( "Fiji", 'activities' ),
      "FI" => esc_html__( "Finland", 'activities' ),
      "FR" => esc_html__( "France", 'activities' ),
      "GF" => esc_html__( "French Guiana", 'activities' ),
      "PF" => esc_html__( "French Polynesia", 'activities' ),
      "TF" => esc_html__( "French Southern Territories", 'activities' ),
      "GA" => esc_html__( "Gabon", 'activities' ),
      "GM" => esc_html__( "Gambia", 'activities' ),
      "GE" => esc_html__( "Georgia", 'activities' ),
      "DE" => esc_html__( "Germany", 'activities' ),
      "GH" => esc_html__( "Ghana", 'activities' ),
      "GI" => esc_html__( "Gibraltar", 'activities' ),
      "GR" => esc_html__( "Greece", 'activities' ),
      "GL" => esc_html__( "Greenland", 'activities' ),
      "GD" => esc_html__( "Grenada", 'activities' ),
      "GP" => esc_html__( "Guadeloupe", 'activities' ),
      "GU" => esc_html__( "Guam", 'activities' ),
      "GT" => esc_html__( "Guatemala", 'activities' ),
      "GN" => esc_html__( "Guinea", 'activities' ),
      "GW" => esc_html__( "Guinea-Bissau", 'activities' ),
      "GY" => esc_html__( "Guyana", 'activities' ),
      "HT" => esc_html__( "Haiti", 'activities' ),
      "HM" => esc_html__( "Heard Island and Mcdonald Islands", 'activities' ),
      "VA" => esc_html__( "Holy See (Vatican City State)", 'activities' ),
      "HN" => esc_html__( "Honduras", 'activities' ),
      "HK" => esc_html__( "Hong Kong", 'activities' ),
      "HU" => esc_html__( "Hungary", 'activities' ),
      "IS" => esc_html__( "Iceland", 'activities' ),
      "IN" => esc_html__( "India", 'activities' ),
      "ID" => esc_html__( "Indonesia", 'activities' ),
      "IR" => esc_html__( "Iran, Islamic Republic of", 'activities' ),
      "IQ" => esc_html__( "Iraq", 'activities' ),
      "IE" => esc_html__( "Ireland", 'activities' ),
      "IL" => esc_html__( "Israel", 'activities' ),
      "IT" => esc_html__( "Italy", 'activities' ),
      "JM" => esc_html__( "Jamaica", 'activities' ),
      "JP" => esc_html__( "Japan", 'activities' ),
      "JO" => esc_html__( "Jordan", 'activities' ),
      "KZ" => esc_html__( "Kazakhstan", 'activities' ),
      "KE" => esc_html__( "Kenya", 'activities' ),
      "KI" => esc_html__( "Kiribati", 'activities' ),
      "KP" => esc_html__( "Korea, Democratic People's Republic of", 'activities' ),
      "KR" => esc_html__( "Korea, Republic of", 'activities' ),
      "KW" => esc_html__( "Kuwait", 'activities' ),
      "KG" => esc_html__( "Kyrgyzstan", 'activities' ),
      "LA" => esc_html__( "Lao People's Democratic Republic", 'activities' ),
      "LV" => esc_html__( "Latvia", 'activities' ),
      "LB" => esc_html__( "Lebanon", 'activities' ),
      "LS" => esc_html__( "Lesotho", 'activities' ),
      "LR" => esc_html__( "Liberia", 'activities' ),
      "LY" => esc_html__( "Libyan Arab Jamahiriya", 'activities' ),
      "LI" => esc_html__( "Liechtenstein", 'activities' ),
      "LT" => esc_html__( "Lithuania", 'activities' ),
      "LU" => esc_html__( "Luxembourg", 'activities' ),
      "MO" => esc_html__( "Macao", 'activities' ),
      "MK" => esc_html__( "Macedonia, the Former Yugoslav Republic of", 'activities' ),
      "MG" => esc_html__( "Madagascar", 'activities' ),
      "MW" => esc_html__( "Malawi", 'activities' ),
      "MY" => esc_html__( "Malaysia", 'activities' ),
      "MV" => esc_html__( "Maldives", 'activities' ),
      "ML" => esc_html__( "Mali", 'activities' ),
      "MT" => esc_html__( "Malta", 'activities' ),
      "MH" => esc_html__( "Marshall Islands", 'activities' ),
      "MQ" => esc_html__( "Martinique", 'activities' ),
      "MR" => esc_html__( "Mauritania", 'activities' ),
      "MU" => esc_html__( "Mauritius", 'activities' ),
      "YT" => esc_html__( "Mayotte", 'activities' ),
      "MX" => esc_html__( "Mexico", 'activities' ),
      "FM" => esc_html__( "Micronesia, Federated States of", 'activities' ),
      "MD" => esc_html__( "Moldova, Republic of", 'activities' ),
      "MC" => esc_html__( "Monaco", 'activities' ),
      "MN" => esc_html__( "Mongolia", 'activities' ),
      "MS" => esc_html__( "Montserrat", 'activities' ),
      "MA" => esc_html__( "Morocco", 'activities' ),
      "MZ" => esc_html__( "Mozambique", 'activities' ),
      "MM" => esc_html__( "Myanmar", 'activities' ),
      "NA" => esc_html__( "Namibia", 'activities' ),
      "NR" => esc_html__( "Nauru", 'activities' ),
      "NP" => esc_html__( "Nepal", 'activities' ),
      "NL" => esc_html__( "Netherlands", 'activities' ),
      "AN" => esc_html__( "Netherlands Antilles", 'activities' ),
      "NC" => esc_html__( "New Caledonia", 'activities' ),
      "NZ" => esc_html__( "New Zealand", 'activities' ),
      "NI" => esc_html__( "Nicaragua", 'activities' ),
      "NE" => esc_html__( "Niger", 'activities' ),
      "NG" => esc_html__( "Nigeria", 'activities' ),
      "NU" => esc_html__( "Niue", 'activities' ),
      "NF" => esc_html__( "Norfolk Island", 'activities' ),
      "MP" => esc_html__( "Northern Mariana Islands", 'activities' ),
      "NO" => esc_html__( "Norway", 'activities' ),
      "OM" => esc_html__( "Oman", 'activities' ),
      "PK" => esc_html__( "Pakistan", 'activities' ),
      "PW" => esc_html__( "Palau", 'activities' ),
      "PS" => esc_html__( "Palestinian Territory, Occupied", 'activities' ),
      "PA" => esc_html__( "Panama", 'activities' ),
      "PG" => esc_html__( "Papua New Guinea", 'activities' ),
      "PY" => esc_html__( "Paraguay", 'activities' ),
      "PE" => esc_html__( "Peru", 'activities' ),
      "PH" => esc_html__( "Philippines", 'activities' ),
      "PN" => esc_html__( "Pitcairn", 'activities' ),
      "PL" => esc_html__( "Poland", 'activities' ),
      "PT" => esc_html__( "Portugal", 'activities' ),
      "PR" => esc_html__( "Puerto Rico", 'activities' ),
      "QA" => esc_html__( "Qatar", 'activities' ),
      "RE" => esc_html__( "Reunion", 'activities' ),
      "RO" => esc_html__( "Romania", 'activities' ),
      "RU" => esc_html__( "Russian Federation", 'activities' ),
      "RW" => esc_html__( "Rwanda", 'activities' ),
      "SH" => esc_html__( "Saint Helena", 'activities' ),
      "KN" => esc_html__( "Saint Kitts and Nevis", 'activities' ),
      "LC" => esc_html__( "Saint Lucia", 'activities' ),
      "PM" => esc_html__( "Saint Pierre and Miquelon", 'activities' ),
      "VC" => esc_html__( "Saint Vincent and the Grenadines", 'activities' ),
      "WS" => esc_html__( "Samoa", 'activities' ),
      "SM" => esc_html__( "San Marino", 'activities' ),
      "ST" => esc_html__( "Sao Tome and Principe", 'activities' ),
      "SA" => esc_html__( "Saudi Arabia", 'activities' ),
      "SN" => esc_html__( "Senegal", 'activities' ),
      "CS" => esc_html__( "Serbia and Montenegro", 'activities' ),
      "SC" => esc_html__( "Seychelles", 'activities' ),
      "SL" => esc_html__( "Sierra Leone", 'activities' ),
      "SG" => esc_html__( "Singapore", 'activities' ),
      "SK" => esc_html__( "Slovakia", 'activities' ),
      "SI" => esc_html__( "Slovenia", 'activities' ),
      "SB" => esc_html__( "Solomon Islands", 'activities' ),
      "SO" => esc_html__( "Somalia", 'activities' ),
      "ZA" => esc_html__( "South Africa", 'activities' ),
      "GS" => esc_html__( "South Georgia and the South Sandwich Islands", 'activities' ),
      "ES" => esc_html__( "Spain", 'activities' ),
      "LK" => esc_html__( "Sri Lanka", 'activities' ),
      "SD" => esc_html__( "Sudan", 'activities' ),
      "SR" => esc_html__( "Suriname", 'activities' ),
      "SJ" => esc_html__( "Svalbard and Jan Mayen", 'activities' ),
      "SZ" => esc_html__( "Swaziland", 'activities' ),
      "SE" => esc_html__( "Sweden", 'activities' ),
      "CH" => esc_html__( "Switzerland", 'activities' ),
      "SY" => esc_html__( "Syrian Arab Republic", 'activities' ),
      "TW" => esc_html__( "Taiwan, Province of China", 'activities' ),
      "TJ" => esc_html__( "Tajikistan", 'activities' ),
      "TZ" => esc_html__( "Tanzania, United Republic of", 'activities' ),
      "TH" => esc_html__( "Thailand", 'activities' ),
      "TL" => esc_html__( "Timor-Leste", 'activities' ),
      "TG" => esc_html__( "Togo", 'activities' ),
      "TK" => esc_html__( "Tokelau", 'activities' ),
      "TO" => esc_html__( "Tonga", 'activities' ),
      "TT" => esc_html__( "Trinidad and Tobago", 'activities' ),
      "TN" => esc_html__( "Tunisia", 'activities' ),
      "TR" => esc_html__( "Turkey", 'activities' ),
      "TM" => esc_html__( "Turkmenistan", 'activities' ),
      "TC" => esc_html__( "Turks and Caicos Islands", 'activities' ),
      "TV" => esc_html__( "Tuvalu", 'activities' ),
      "UG" => esc_html__( "Uganda", 'activities' ),
      "UA" => esc_html__( "Ukraine", 'activities' ),
      "AE" => esc_html__( "United Arab Emirates", 'activities' ),
      "GB" => esc_html__( "United Kingdom", 'activities' ),
      "US" => esc_html__( "United States", 'activities' ),
      "UM" => esc_html__( "United States Minor Outlying Islands", 'activities' ),
      "UY" => esc_html__( "Uruguay", 'activities' ),
      "UZ" => esc_html__( "Uzbekistan", 'activities' ),
      "VU" => esc_html__( "Vanuatu", 'activities' ),
      "VE" => esc_html__( "Venezuela", 'activities' ),
      "VN" => esc_html__( "Viet Nam", 'activities' ),
      "VG" => esc_html__( "Virgin Islands, British", 'activities' ),
      "VI" => esc_html__( "Virgin Islands, U.s.", 'activities' ),
      "WF" => esc_html__( "Wallis and Futuna", 'activities' ),
      "EH" => esc_html__( "Western Sahara", 'activities' ),
      "YE" => esc_html__( "Yemen", 'activities' ),
      "ZM" => esc_html__( "Zambia", 'activities' ),
      "ZW" => esc_html__( "Zimbabwe", 'activities' )
    );
  }

  /**
   * Formats date for display
   *
   * @param 	string 	date_string Date to format
   * @return 	string 	Formattet date or --
   */
  static function format_date( $date_string ) {
  	if ( $date_string == '0000-00-00 00:00:00' ) {
  		return '&mdash;';
  	}
  	$date = date_create( $date_string );
  	return date_format( $date, get_option( 'date_format' ) );
  }
}
