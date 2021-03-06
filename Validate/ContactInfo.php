<?php



/**
 * Used to validate various strings
 * @author Paul Visco
 * @version 1.2 03/18/2008
 * @package sb_Validate
 *
 */
class sb_Validate_ContactInfo{


	/**
	 * Validates an email address format and checks DNS record.  Does not include the whole spec for vlid emails, only accepts one @ symbol, letters, numbers, and . _ - + ! as special characters
	 *
	 * @author Paul Visco
	 * @version 1.2 13/18/2008
	 * @param string $email
	 * @param string $check_mx_records Check the MX record at the dns to make sure the mail host exists
	 * @return sb_Validate_Results
	 */
	public static function email($email, $check_mx_records=true){

		$result = new sb_Validate_Results();
		$result->value = $email;

		if(preg_match("/^[\w-!\+]+(\.[\w-!\+]+)*@[\w-]+(\.[\w-]+)*(\.[\w]{2,4})$/", $email)){
			list($name,$domain)=explode('@',$email);

			if(!checkdnsrr($domain,'MX')) {
				$result->is_valid = false;
				$result->message = 'Invalid domain name or mail server down for this address';
			} else {
				$result->message = 'Valid format and domain checked';
				$result->is_valid = true;
			}
		} else {
			$result->is_valid = false;
			$result->message = 'Invalid format';
		}

		return $result;
	}

	/**
	 * Validates a zip code
	 *
	 * @author Paul Visco
	 * @version 1.2 13/18/2008
	 * @param string $zip The zip code to validate in xxxxx or xxxxx-xxxx format
	 * @param boolean check_usps Check the usps sie look for validation

	 * @return sb_Validate_Results The message property includes the city if it exists
	 */
	public static function zip($zip, $check_usps=true){

		$result = new sb_Validate_Results();
		$result->value = $zip;
		$result->is_valid = false;

		if(preg_match("/^(\d{5})(-\d{4})*$/", $zip)){
			$result->message = "Valid zip code format";
			$result->is_valid = true;

			if($check_usps){

				$page = @file_get_contents("http://zip4.usps.com/zip4/zcl_3_results.jsp?zip5=".substr($zip, 0, 5)."&submit.x=50&submit.y=5&submit=Find+ZIP+Code");

				if(!$page){
					$result->message .= ' cannot reach USPS site to validate zip code existence';
				} else {
					preg_match("~<b>(.*?)</b>~", $page, $city);
					if(isset($city[1])){

						$data = explode(",", strtolower($city[1]));

						$result->city = ucwords(trim($data[0]));
						$result->state = strtoupper(trim($data[1]));
						$result->message .= " for ".$result->city.','.$result->state;

					} else {
						$result->message .= " but city not found!";
						$result->is_valid = false;
					}
				}
			}

		} else {
			$result->message = "Invalid zip code format ";
		}


		return $result;
	}

	/**
	 * Validates a phone number.  Without a modem we can only validate format ;(
	 * @author Paul Visco
	 * @version 1.2 13/18/2008
	 * @param string $phone The phone number to validate shoudl be in  xxx-xxx-xxxx format

	 * @return sb_Validate_Results
	 */
	public static function phone($phone){

		$result = new sb_Validate_Results();
		$result->value = $phone;
		$result->is_valid = false;

		if(preg_match("/^\d{3}-\d{3}-\d{4}$/", $phone)){
			$result->message = "Valid phone number";
			$result->is_valid = true;

		} else {
			$result->message = "Invalid phone number";
		}

		return $result;
	}

	/**
	 * Validates a url.  Also checks to make sure the page is reachable and has HTML Tag
	 * @author Paul Visco
	 * @version 1.2 13/18/2008
	 *
	 * @param string $url The url to validate should

	 * @return sb_Validate_Results
	 */
	public static function url($url, $check_url=true){

		$result = new sb_Validate_Results();
		$result->value = $url;
		$result->is_valid = false;
		$result->data = new stdClass();

		//  /(\s|\n)([a-z]+?):\/\/([a-z0-9\-\.,\?!%\*_\#:;~\\&$@\/=\+]+)/i

		if(preg_match("/^http:\/\/([a-z0-9\-\.,\?!%\*_\#:;~\\&$@\/=\+]+)$/i", $url)){

			$result->message = "Valid url format";
			$result->is_valid = true;

			if($check_url){
				$page = @file_get_contents($url);
				if(!$page){

					$result->is_valid = false;
					$result->message .= ' but page not loaded';
					$result->data->header = $http_response_header[0];

				} else {

					if(!preg_match("/<html/", $page)){
						$result->is_valid = false;
						$result->message .= " page reachable but no html tag found";
					} else {
						$result->message .= " and page loaded";
					}
				}
			}


		} else {
			$result->message = "Invalid url format";
		}

		return $result;
	}

	/**
	 * Validates state two character abbr
	 * @author Paul Visco
	 * @version 1.2 13/18/2008
	 *
	 * @param string $state
	 * @return sb_Validate_Results
	 */
	public static function state($state){

		$result = new sb_Validate_Results();
		$result->value = $state;

		if(in_array($state, array('AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC', 'DE', 'FL', 'GA', 'HI', 'IA', 'ID', 'IL', 'IN', 'KS', 'KY', 'LA', 'MA', 'MD', 'ME', 'MI', 'MN', 'MO', 'MS', 'MT', 'NC', 'ND', 'NE', 'NH', 'NJ', 'NM', 'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VA', 'VT', 'WA', 'WV', 'WY'))){

			$result->is_valid = true;
			$result->message = 'Valid state code';

		} else {

			$result->is_valid = false;
			$result->message = 'Invalid state code, are you sure you are using a two letter abbreviation';

		}

		return $result;
	}

	/**
	 * Validates canadian province two character abbr
	 * @author Paul Visco
	 * @version 1.2 13/18/2008
	 *
	 * @param string $province
	 * @return sb_Validate_Results
	 */
	public static function province($province){

		$result = new sb_Validate_Results();
		$result->value = $province;

		if(in_array($province, array('AB', 'BC', 'MB', 'NB', 'NL', 'NS', 'NT', 'NU', 'ON', 'PE', 'QC', 'SK', 'YT'))){

			$result->is_valid = true;
			$result->message = 'Valid province code';

		} else {

			$result->is_valid = false;
			$result->message = 'Invalid province code, are you sure you are using a two letter abbreviation';

		}

		return $result;
	}


}
?>
