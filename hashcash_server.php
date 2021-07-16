<?php

// set this to the address you want to receive the form submissions
const YOUR_EMAIL_ADDRESS = "";
// the web page to which you want to redirect upon successful form submission
const SUCCESS_URL = "";

// set this to a random string
const HASHCASH_SALT = "";

/*
 * number of bits to collide
 * Approximate number of hash guesses needed for difficulty target of:
 * 1-4: 10
 * 5-8: 100
 * 9-12: 1,000
 * 13-16: 10,000
 * 17-20: 100,000
 * 21-24: 1,000,000
 * 25-28: 10,000,000
 * 29-32: 100,000,000
 */
const HASHCASH_DIFFICULTY = 18;

// time flexibility, in minutes, between stamp generation and expiration
// allows time for clock drift and for filling out form
// Note that higher values require higher resources to validate
// that a given puzzle has not expired
const HASHCASH_TIME_WINDOW = 10;

// validate & process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
  if (empty($_POST["name"])) {
    $nameError = "Name is required";
  } else {
    $name = sanitize_input($_POST["name"]);
    // check if name only contains letters and whitespace
    if (!preg_match("/^[a-zA-Z ]*$/",$name)) {
      $nameError = "Only letters and white space allowed";
    }
  }
  
  if (empty($_POST["email"])) {
    $emailError = "Email is required";
  } else {
    $email = sanitize_input($_POST["email"]);
    // check if e-mail address is well-formed
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $emailError = "Invalid email format";
    }
  }
    
  if (empty($_POST["subject"])) {
    $subjectError = "Subject is required";
  } else {
    $subject = sanitize_input($_POST["subject"]);
  }
  
  if (empty($_POST["emailBody"])) {
    $messageError = "Message is required";
  } else {
    $emailBody = sanitize_input($_POST["emailBody"]);
  }

  if (!hc_CheckStamp()) {
    $formError = 'Invalid proof of work submitted! Please try again.';
  }

  if ($nameError || $emailError || $subjectError || $messageError || $formError)
    return;

  // Form submission validated; send message
  $name = $_POST['name'];
  $subject = $_POST['subject'];
  $emailBody = $name . " from email: " . $email . " wrote the following:" . "\n\n" . $_POST['emailBody'];
  $headers = "From: $email\r\nReply-to: $email";
  mail(YOUR_EMAIL_ADDRESS, $subject, $emailBody, $headers);
  // Redirect to a success page of your choosing
  echo '<META HTTP-EQUIV="Refresh" Content="0; URL=' . SUCCESS_URL . '">';
  exit;
}

function sanitize_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

// Attempt to determine the client's IP address
function get_client_ip() {
  if (getenv('HTTP_CLIENT_IP'))
    return getenv('HTTP_CLIENT_IP');
  else if(getenv('HTTP_X_FORWARDED_FOR'))
    return getenv('HTTP_X_FORWARDED_FOR');
  else if(getenv('HTTP_X_FORWARDED'))
    return getenv('HTTP_X_FORWARDED');
  else if(getenv('HTTP_FORWARDED_FOR'))
    return getenv('HTTP_FORWARDED_FOR');
  else if(getenv('HTTP_FORWARDED'))
    return getenv('HTTP_FORWARDED');
  else if(getenv('REMOTE_ADDR'))
    return getenv('REMOTE_ADDR');
  return 'UNKNOWN';
}

// drop in your desired hash function here
function hc_HashFunc($x) {
  return hash('sha256', $x);
}

// Uncomment the echo statement to get debug info printed to the browser
function PRINT_DEBUG($x) {
  //echo "<pre>$x</pre>\n";
}

// Get the first num_bits of data from this string
function hc_ExtractBits($hex_string, $num_bits) {
  $bit_string = "";
  $num_chars = ceil($num_bits / 4);
  for($i = 0; $i < $num_chars; $i++)
    $bit_string .= str_pad(base_convert($hex_string[$i], 16, 2), 4, "0", STR_PAD_LEFT); // convert hex to binary and left pad with 0s

  PRINT_DEBUG("requested $num_bits bits from $hex_string, returned $bit_string as " . substr($bit_string, 0, $num_bits));
  return substr($bit_string, 0, $num_bits);
}

// generate a stamp
function hc_CreateStamp() {
  $ip = get_client_ip();
  $now = intval(time() / 60);

  // stamp = hash of time (in minutes) . user ip . salt value
  $stamp = hc_HashFunc($now . $ip . HASHCASH_SALT);

  // embed stamp in page
  echo "<input type=\"hidden\" name=\"hc_stamp\" id=\"hc_stamp\" value=\"" . $stamp . "\" />\n";
  echo "<input type=\"hidden\" name=\"hc_difficulty\" id=\"hc_difficulty\" value=\"" . HASHCASH_DIFFICULTY . "\" />\n";

  // set nonce value if it has already been generated and is valid so that it persists in case there are other form errors
  if (isset($_POST['hc_nonce']) && hc_CheckStamp()) {
    echo "<input type=\"hidden\" name=\"hc_nonce\" id=\"hc_nonce\" value=\"" . $_POST['hc_nonce'] . "\" />\n";
  } else {
    echo "<input type=\"hidden\" name=\"hc_nonce\" id=\"hc_nonce\" value=\"\" />\n";
  }
}

// check that the stamp is within our allowed time window
// this function also implicitly validates that the IP address and salt match
function hc_CheckExpiration($a_stamp) {
  $tempnow = intval(time() / 60);
  $ip = get_client_ip();

  // gen hashes for $tempnow - $tolerance to $tempnow + $tolerance
  for($i = -1*HASHCASH_TIME_WINDOW; $i < HASHCASH_TIME_WINDOW; $i++) {
    PRINT_DEBUG("checking $a_stamp versus " . hc_HashFunc(($tempnow - $i) . $ip . HASHCASH_SALT));
    if($a_stamp === hc_HashFunc(($tempnow + $i) . $ip . HASHCASH_SALT)) {
      PRINT_DEBUG("stamp matched at " . $i . " minutes from now");
      return true;
    }
  }

  PRINT_DEBUG("stamp expired");
  return false;
}

// check that the hash of the stamp + nonce meets the difficulty target
function hc_CheckProofOfWork($difficulty, $stamp, $nonce) {

  // get hash of $stamp & $nonce
  PRINT_DEBUG("checking $difficulty bits of work");
  $work = hc_HashFunc($stamp . $nonce);

  $leadingBits = hc_ExtractBits($work, $difficulty);

  PRINT_DEBUG("checking $leadingBits leading bits of $work for difficulty $difficulty match");

  // if the leading bits are all 0, the difficulty target was met
  return (strlen($leadingBits) > 0 && intval($leadingBits) == 0);
}

// checks validity, expiration, and difficulty target for a stamp
function hc_CheckStamp() {
  $stamp = $_POST['hc_stamp'];
  $client_difficulty = $_POST['hc_difficulty'];
  $nonce = $_POST['hc_nonce'];

  PRINT_DEBUG("stamp: $stamp");
  PRINT_DEBUG("difficulty: $client_difficulty");
  PRINT_DEBUG("nonce: $nonce");

  PRINT_DEBUG("difficulty comparison: $client_difficulty vs " . HASHCASH_DIFFICULTY);
  if ($client_difficulty != HASHCASH_DIFFICULTY) return false;

  $expectedLength = strlen(hc_HashFunc(uniqid()));
  PRINT_DEBUG("stamp size: " . strlen($stamp) . " expected: $expectedLength");
  if(strlen($stamp) != $expectedLength) return false;

  if(hc_CheckExpiration($stamp)) {
    PRINT_DEBUG("PoW puzzle has not expired");
  } else {
    PRINT_DEBUG("PoW puzzle expired");
    return false;
  }

  // check the actual PoW
  if(hc_CheckProofOfWork(HASHCASH_DIFFICULTY, $stamp, $nonce)) {
    PRINT_DEBUG("Difficulty target met.");
  } else {
    PRINT_DEBUG("Difficulty target was not met.");
    return false;
  }

  return true;
}
?>