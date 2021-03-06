# hashcash form protect (JS & PHP)
Boilerplate code for protecting a form with proof of work. 
Uses javascript in the browser to generate the hashcash and PHP on the server to generate the puzzle and validate the proof of work.

There are 3 components to this project:
1. `hashcash_client.js` - the javascript you need to include on your form's web page. This contains the cryptographic functions that do the brute forcing of the puzzle created by the server.
2. `hashcash_server.php` - this also needs to be included in your form so that the server generates the appropriate puzzle.
3. `form.php` - the simple HTML that pulls it all together; your actual form will submit to itself and validate the form details along with the hashcash on the server side. If there are problems, errors will display on the form. If everything validates, the server will redirect to a success page.

There are 4 variables that you need to configure, all in `hashcash_server.php`:
1. YOUR_EMAIL_ADDRESS - set this to the address you want to receive the form submissions
2. SUCCESS_URL - the web page to which you want to redirect upon successful form submission
3. HASHCASH_SALT - any random sequence of characters should suffice - just keep them secret
4. STAMP_LOG - a file name to keep track of recently used stamps, such as "stamps.log"

This project was inspired by [hashcash-js](https://github.com/007/hashcash-js) and updated to use a more modern hash function and PHP / JS functions.
