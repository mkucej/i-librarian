<!DOCTYPE html>
<html style="width:100%;height:100%">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script type="text/javascript" src="js/jquery.js"></script>
    </head>
    <body style="margin:0;border:0;padding:0 40px">
        <h3>
        <?php
        print 'Fatal Error! Directory: "' . $bad_path . '" must be writable by the web server user.';
        ?>
        </h3>
        <script type="text/javascript">
            $('#first-loader', window.parent.document).fadeOut(400,function(){$(this).remove()});
        </script>
    </body>
</html>