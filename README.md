First off thank you for using my court system! Alot of work went into this and took me quite a while!

If you do find any bugs, have questions, concerns, curious about my other scripts or services or anything else please visit me in my development discord!

Discord: http://discord.gg/HW5atYDh6m

Discord: shawnblackwood


------------------------------------------
**THIS DOESN'T WORK ON GAME PANELS, THIS MUST BE A VPS.**
---------------Installation---------------

YOU WILL NEED:
    - XAMPP
    - PHPMYSQL (COMES WITH XAMPP)
    - PHP



Import the SQL into a database called courtsystem

Put the Web Version (IF YOU WANT IT) inside C:/xampp/htdocs

If you want to use both the FiveM + The Web Version place the Web Version as previously instructed, then put courttablet in C:/xampp/htdocs (DO NOT UNPACK THIS). Both SQLs are the same and do not need imported again.

Insert sd-tablet into your server and configure to your liking.


IF YOUR SERVER USES HTTPS (SSL) YOU WILL NEED TO CHANGE THE URL IN THE CONFIG and html/js/script.js TO MATCH YOUR SERVERS URL (https://localhost) instead of http://localhost/ otherwise your tablet will not work.

If you're using QBOX, DO NOT make your Server database courtsystem as QBOX has a users field and will conflict with your tablet. If you did do this, please make another database and name it something else, then please correct it in the HTML FiveM version in includes/database.php with the new database name!


Last Step:

ENJOY!
