<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css"/>
<title>Insane Picture Search</title>
<link rel="icon" type="image/ico" href="images/favicon.ico"/>
</head>

<body>
    <!--Upload button-->
    <div id="upload">
        <div id="upload-container">
            <div id="menu-upload">
                <ul>
                    <li>
                        <a id="superButton"><div id="menu-upload-text"><img src="images/upload.png" style="float: left; margin-top: 2px;">&nbsp;Upload data <img src="images/drop.png"></div></a>
                        <ul id="menu-square">
                            <li><a href="index.php?page=upload.php&action=upload" title="Upload images to the system"><div id="menu-upload-text"><img src="images/new.png" style="float: left; margin-top: 2px;">&nbsp;Upload images</div></a></li>
                            <!--<li><a href="index.php?page=upload.php&action=db-details" title="Manage active database"><div id="menu-upload-text"><img src="images/edit.png" style="float: left; margin-top: 2px;">&nbsp;Manage database</div></a></li>-->
                            <li><a href="index.php?page=help.php" title="View a simple help guide about searching, downloading etc"><div id="menu-upload-text"><img src="images/d-edit.png" style="float: left; margin-top: 2px;">&nbsp;Help</div></a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div id="header" onclick="location.href='index.php'" style="cursor: pointer;"></div>
    <script type="text/javascript">
        var d = document.getElementById('superButton');
        d.onclick = function() {
            var e = document.getElementById('menu-square');
            if(e.style.visibility == 'hidden' || e.style.visibility == '') {
                e.style.visibility = 'visible';
            } else {
                e.style.visibility = 'hidden';
            }
        };

        var superButton = document.getElementById('superButton');
        var uploadList = document.getElementById('menu-square');
    </script>
    <div id="page">
        <?php if(!isset($_GET['page'])) {?>
        <form id="search-form" action="index.php?page=search.php" method="post" enctype="multipart/form-data">
            <div id="details">Search for relevant pictures by uploading a new one</div>
            <input accept="image*/" type="file" name="fileToUpload" id="fileToUpload" required="required"/>
            <div id=limit>
                <select id="limiter" name="limiter" required="required" title="Limit the number of results">
                    <option value="10">Top 10 images</option>
                    <option value="20" selected="selected">Top 20 images</option>
                    <option value="50">Top 50 images</option>
                    <option value="100">Top 100 images</option>
                    <option value="200">Top 200 images</option>
                    <option value="500">Top 500 images</option>
                </select>
            </div>
            <input type="submit" value="Upload & Search" name="submit" id="search"/>
            <br/><div id="note">Note that you can only upload PNG and JPEG (JPG) with up to 1GB file limit per image.</div>
            
        </form>
        <?php } else {
            $page = $_GET['page'];
            include $page;
        }
        ?>
    </div>
    <div id="footer">
        <div id="footer-container">
            <div style="float: left; margin: 0 auto" id="emblem"><img src="images/emblem-1.png"></div>
            <div id="text-footer">
                <br/>
                <div style="font-size: 20px;">Insane Image Search</div>
                &copy; <?php echo date('Y'); ?> Paraskevas E. - Tzanakas A.
                <br/>
                A project for the course of Multimedia Database Systems, PSP of the Informatics Dept, AUTh
                <br/>
            </div>
        </div>
    </div>
</body> 
</html>