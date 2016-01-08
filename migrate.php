<?php
//THIS SCRIPT UPGRADES I, LIBRARIAN DATABASES FROM 2.0 to 2.1 FORMAT
die("Upgrading from version <2.1 is no longer supported.");
ignore_user_abort();

include_once 'data.php';
include_once 'functions.php';

//create new copy and backup existing library.sq3

    if (is_file($database_path.'library.sq3') && !is_file($database_path.'library.sq3.old')) {

        $fp = fopen($database_path.'library.sq3', "r");

        if (flock($fp, LOCK_EX)) {

            if (is_file($database_path.'library.sq3.old')) {
                flock($fp, LOCK_UN);
                fclose($fp);
                die();
            }

            $copy1 = copy($database_path.'library.sq3', $database_path.'library.sq3.old');
            $copy2 = copy($database_path.'fulltext.sq3', $database_path.'fulltext.sq3.old');
            copy($database_path.'library.sq3', $database_path.'newlibrary.sq3');

            if (!$copy1 || !$copy2) die('Error! Could not create database backup copy.');

        //create users database, create and populate tables users and settings

            try {
                    $dbHandle = new PDO('sqlite:'.$database_path.'users.sq3');
            } catch (PDOException $e) {
                    print "Error: ".$e->getMessage()."<br/>";
                    print "PHP extensions PDO and PDO_SQLite must be installed.";
                    die();
            }

            if($dbHandle) {

                $dbHandle->exec("ATTACH DATABASE '".$database_path."newlibrary.sq3' AS librarydatabase");

                $dbHandle->beginTransaction();

                $dbHandle->exec("CREATE TABLE users (
                    userID integer PRIMARY KEY,
                    username text UNIQUE NOT NULL DEFAULT '',
                    password text NOT NULL DEFAULT '',
                    permissions text NOT NULL DEFAULT 'U'
                    )");

                $dbHandle->exec("UPDATE librarydatabase.shelves SET user='' WHERE user IS NULL");
                $dbHandle->exec("UPDATE librarydatabase.shelves SET password='' WHERE password IS NULL");
                $dbHandle->exec("UPDATE librarydatabase.shelves SET files='' WHERE files IS NULL");
                $dbHandle->exec("UPDATE librarydatabase.shelves SET permissions='' WHERE permissions IS NULL");

                $dbHandle->exec("INSERT INTO users SELECT id,user,password,permissions FROM librarydatabase.shelves");

                $dbHandle->exec("CREATE TABLE settings (
                    userID integer NOT NULL DEFAULT '',
                    setting_name text NOT NULL DEFAULT '',
                    setting_value text NOT NULL DEFAULT ''
                    )");

                $dbHandle->exec("UPDATE librarydatabase.settings SET user='' WHERE user IS NULL");
                $dbHandle->exec("UPDATE librarydatabase.settings SET setting_name='' WHERE setting_name IS NULL");
                $dbHandle->exec("UPDATE librarydatabase.settings SET setting_value='' WHERE setting_value IS NULL");

                $dbHandle->exec("INSERT INTO settings SELECT user,setting_name,setting_value FROM librarydatabase.settings");
                $dbHandle->exec("UPDATE OR IGNORE settings SET userID=(SELECT userID FROM users WHERE username=settings.userID)");
                $dbHandle->exec("DROP TABLE librarydatabase.settings");
                $dbHandle->commit();
            }

            $dbHandle->exec("DETACH DATABASE librarydatabase");
            $dbHandle = null;

        //create new shelves and populate

            database_connect($database_path, 'newlibrary');

            $dbHandle->beginTransaction();

            $dbHandle->exec("CREATE TABLE shelves2 (
                fileID integer NOT NULL DEFAULT '',
                userID integer NOT NULL DEFAULT '',
                UNIQUE (fileID,userID)
                )");

            $result = $dbHandle->query("SELECT id,files FROM shelves");

            while ($shelf = $result->fetch(PDO::FETCH_ASSOC)) {
                $userID = null;
                $userID = $shelf['id'];
                $shelf_files = array();
                $shelf_files = explode("|", $shelf['files']);
                while (list($key,$fileID) = each($shelf_files)) {
                    if ($fileID > 0) $dbHandle->exec("INSERT OR IGNORE INTO shelves2 VALUES ($fileID,$userID)");
                }
            }

            $result = null;

            $dbHandle->exec("DROP TABLE shelves");
            $dbHandle->exec("ALTER TABLE shelves2 RENAME TO shelves");
            $dbHandle->exec("DELETE FROM shelves WHERE fileID IN (SELECT fileID FROM shelves EXCEPT SELECT id FROM library)");

            $dbHandle->commit();

        // create tables categories and filescategories and populate them

            $dbHandle->exec("UPDATE library SET category='' WHERE category IS NULL");

            $result = $dbHandle->query("SELECT category FROM library");
            $category_array = $result->fetchAll(PDO::FETCH_COLUMN);
            $result = null;
            $category_string = implode ('|', $category_array);
            $category_array = explode ("|", $category_string);
            $category_array = array_unique($category_array);
            $category_array = array_filter($category_array);
            $key_to_remove = array_search('!unassigned', $category_array);
            unset($category_array[$key_to_remove]);
            $dbHandle->beginTransaction();
            $create = $dbHandle->exec("CREATE TABLE categories (categoryID integer PRIMARY KEY, category text NOT NULL DEFAULT '')");
            foreach($category_array as $string) {
                $string = $dbHandle->quote($string);
                $dbHandle->exec("INSERT OR IGNORE INTO categories (category) VALUES ($string)");
            }

            $dbHandle->exec("CREATE TABLE filescategories (fileID integer NOT NULL, categoryID integer NOT NULL, UNIQUE(fileID,categoryID))");

            $result = $dbHandle->query("SELECT id,category FROM library");
            while($item = $result->fetch(PDO::FETCH_ASSOC)) {
                $category_array = array();
                $category_array = explode ("|", $item['category']);
                $category_array = array_unique($category_array);
                $category_array = array_filter($category_array);
                while (list($key,$category_string) = each($category_array)) {
                    $category_query = $dbHandle->quote($category_string);
                    $dbHandle->exec("INSERT OR IGNORE INTO filescategories (fileID, categoryID) VALUES ($item[id], (SELECT categoryID FROM categories WHERE category=$category_query))");
                }
            }

            $dbHandle->commit();
            $result = null;
            $result2 = null;

        //    create tables projects, projectsfiles, projectsusers

            $dbHandle->exec("ATTACH DATABASE '".$database_path."users.sq3' AS usersdatabase");

            $dbHandle->beginTransaction();
            $dbHandle->exec("CREATE TABLE projects (projectID integer PRIMARY KEY, userID integer NOT NULL, project text NOT NULL)");
            $dbHandle->exec("CREATE TABLE projectsfiles (projectID integer NOT NULL, fileID integer NOT NULL, UNIQUE (projectID,fileID))");
            $dbHandle->exec("CREATE TABLE projectsusers (projectID integer NOT NULL, userID integer NOT NULL, UNIQUE (projectID,userID))");

            $dbHandle->exec("UPDATE desktop SET user='' WHERE user IS NULL");
            $dbHandle->exec("UPDATE desktop SET project='' WHERE project IS NULL");
            $dbHandle->exec("UPDATE desktop SET files='' WHERE files IS NULL");
            $dbHandle->exec("UPDATE desktop SET access='' WHERE access IS NULL");

            $dbHandle->exec("INSERT OR IGNORE INTO projects SELECT id,user,project FROM desktop");
            $dbHandle->exec("UPDATE OR IGNORE projects SET userID=(SELECT usersdatabase.users.userID FROM usersdatabase.users WHERE projects.userID=usersdatabase.users.username)");

            $result = $dbHandle->query("SELECT id,files,access FROM desktop");

            while ($desktop = $result->fetch(PDO::FETCH_ASSOC)) {
                $projectID = null;
                $projectID = $desktop['id'];
                $desktop_files = array();
                $desktop_files = explode("|", $desktop['files']);
                $desktop_users = array();
                $desktop_users = explode("|", $desktop['access']);
                while (list($key,$fileID) = each($desktop_files)) {
                    $dbHandle->exec("INSERT OR IGNORE INTO projectsfiles VALUES ($projectID,$fileID)");
                }
                while (list($key,$userID) = each($desktop_users)) {
                    $dbHandle->exec("INSERT OR IGNORE INTO projectsusers VALUES ($projectID,$userID)");
                }
            }

            $result = null;
            $dbHandle->exec("DROP TABLE desktop");
            $dbHandle->exec("DELETE FROM projectsfiles WHERE fileID IN (SELECT fileID FROM projectsfiles EXCEPT SELECT id FROM library)");
            $dbHandle->commit();

        //    edit tables notes, searches, library

            $dbHandle->beginTransaction();
            $dbHandle->exec("CREATE TABLE notes2 (notesID integer PRIMARY KEY, userID integer NOT NULL, fileID integer NOT NULL, notes text NOT NULL DEFAULT '')");

            $dbHandle->exec("UPDATE notes SET user='' WHERE user IS NULL");
            $dbHandle->exec("UPDATE notes SET file='' WHERE file IS NULL");
            $dbHandle->exec("UPDATE notes SET notes='' WHERE notes IS NULL");

            $dbHandle->exec("INSERT OR IGNORE INTO notes2 SELECT id,user,file,notes FROM notes");
            $dbHandle->exec("UPDATE OR IGNORE notes2 SET userID=(SELECT usersdatabase.users.userID FROM usersdatabase.users WHERE notes2.userID=usersdatabase.users.username)");
            $dbHandle->exec("DROP TABLE notes");
            $dbHandle->exec("ALTER TABLE notes2 RENAME TO notes");
            $dbHandle->exec("DELETE FROM notes WHERE fileID IN (SELECT fileID FROM notes EXCEPT SELECT id FROM library)");

            $dbHandle->exec("CREATE TABLE searches2 (searchID integer PRIMARY KEY, userID integer NOT NULL,
                searchname text NOT NULL DEFAULT '', searchfield text NOT NULL DEFAULT '', searchvalue text NOT NULL DEFAULT '')");

            $dbHandle->exec("UPDATE searches SET user='' WHERE user IS NULL");
            $dbHandle->exec("UPDATE searches SET searchname='' WHERE searchname IS NULL");
            $dbHandle->exec("UPDATE searches SET searchfield='' WHERE searchfield IS NULL");
            $dbHandle->exec("UPDATE searches SET searchvalue='' WHERE searchvalue IS NULL");

            $dbHandle->exec("INSERT OR IGNORE INTO searches2 SELECT * FROM searches");
            $dbHandle->exec("UPDATE OR IGNORE searches2 SET userID=(SELECT usersdatabase.users.userID FROM usersdatabase.users WHERE searches2.userID=usersdatabase.users.username)");
            $dbHandle->exec("DROP TABLE searches");
            $dbHandle->exec("ALTER TABLE searches2 RENAME TO searches");
            $dbHandle->commit();

            $dbHandle->beginTransaction();
            $dbHandle->exec("CREATE TABLE library2 (
                id integer PRIMARY KEY,
                file text NOT NULL DEFAULT '',
                authors text NOT NULL DEFAULT '',
                affiliation text NOT NULL DEFAULT '',
                title text NOT NULL DEFAULT '',
                journal text NOT NULL DEFAULT '',
                secondary_title text NOT NULL DEFAULT '',
                year text NOT NULL DEFAULT '',
                volume text NOT NULL DEFAULT '',
                issue text NOT NULL DEFAULT '',
                pages text NOT NULL DEFAULT '',
                abstract text NOT NULL DEFAULT '',
                keywords text NOT NULL DEFAULT '',
                editor text NOT NULL DEFAULT '',
                publisher text NOT NULL DEFAULT '',
                place_published text NOT NULL DEFAULT '',
                reference_type text NOT NULL DEFAULT '',
                uid text NOT NULL DEFAULT '',
                doi text NOT NULL DEFAULT '',
                url text NOT NULL DEFAULT '',
                addition_date text NOT NULL DEFAULT '',
                rating integer NOT NULL DEFAULT '',
                authors_ascii text NOT NULL DEFAULT '',
                title_ascii text NOT NULL DEFAULT '',
                abstract_ascii text NOT NULL DEFAULT '',
                added_by integer NOT NULL DEFAULT '',
                modified_by integer NOT NULL DEFAULT '',
                modified_date text NOT NULL DEFAULT '',
                custom1 text NOT NULL DEFAULT '',
                custom2 text NOT NULL DEFAULT '',
                custom3 text NOT NULL DEFAULT '',
                custom4 text NOT NULL DEFAULT ''
                )");

            $dbHandle->exec("UPDATE library SET file='' WHERE file IS NULL");
            $dbHandle->exec("UPDATE library SET authors='' WHERE authors IS NULL");
            $dbHandle->exec("UPDATE library SET title='' WHERE title IS NULL");
            $dbHandle->exec("UPDATE library SET journal='' WHERE journal IS NULL");
            $dbHandle->exec("UPDATE library SET secondary_title='' WHERE secondary_title IS NULL");
            $dbHandle->exec("UPDATE library SET year='' WHERE year IS NULL");
            $dbHandle->exec("UPDATE library SET volume='' WHERE volume IS NULL");
            $dbHandle->exec("UPDATE library SET pages='' WHERE pages IS NULL");
            $dbHandle->exec("UPDATE library SET abstract='' WHERE abstract IS NULL");
            $dbHandle->exec("UPDATE library SET keywords='' WHERE keywords IS NULL");
            $dbHandle->exec("UPDATE library SET editor='' WHERE editor IS NULL");
            $dbHandle->exec("UPDATE library SET publisher='' WHERE publisher IS NULL");
            $dbHandle->exec("UPDATE library SET place_published='' WHERE place_published IS NULL");
            $dbHandle->exec("UPDATE library SET reference_type='' WHERE reference_type IS NULL");
            $dbHandle->exec("UPDATE library SET pmid='' WHERE pmid IS NULL");
            $dbHandle->exec("UPDATE library SET doi='' WHERE doi IS NULL");
            $dbHandle->exec("UPDATE library SET url='' WHERE url IS NULL");
            $dbHandle->exec("UPDATE library SET addition_date='' WHERE addition_date IS NULL");
            $dbHandle->exec("UPDATE library SET rating='' WHERE rating IS NULL");
            $dbHandle->exec("UPDATE library SET authors_ascii='' WHERE authors_ascii IS NULL");
            $dbHandle->exec("UPDATE library SET title_ascii='' WHERE title_ascii IS NULL");
            $dbHandle->exec("UPDATE library SET abstract_ascii='' WHERE abstract_ascii IS NULL");

            $dbHandle->exec("INSERT OR IGNORE INTO library2
                (id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,keywords,editor,publisher,place_published,
                reference_type,uid,doi,url,addition_date,rating,authors_ascii,title_ascii,abstract_ascii)
                SELECT id,file,authors,title,journal,secondary_title,year,volume,pages,abstract,keywords,editor,publisher,place_published,
                reference_type,pmid,doi,url,addition_date,rating,authors_ascii,title_ascii,abstract_ascii FROM library");
            $dbHandle->exec("DROP TABLE library");
            $dbHandle->exec("ALTER TABLE library2 RENAME TO library");
            $dbHandle->exec("UPDATE OR IGNORE library SET added_by=(SELECT min(userID) FROM usersdatabase.users WHERE permissions='A')");
            $dbHandle->commit();

        //  the end

            $dbHandle->exec("DETACH DATABASE usersdatabase");

            $dbHandle->exec("ANALYZE");
            $dbHandle->exec("VACUUM");
            $dbHandle = null;

            database_connect($database_path, 'fulltext');
            
            $dbHandle->beginTransaction();
            $dbHandle->exec("CREATE TABLE full_text2 (
            id INTEGER PRIMARY KEY,
            fileID TEXT NOT NULL DEFAULT '',
            full_text TEXT NOT NULL DEFAULT ''
            )");

            $dbHandle->exec("UPDATE full_text SET file='' WHERE file IS NULL");
            $dbHandle->exec("UPDATE full_text SET full_text='' WHERE full_text IS NULL");

            $dbHandle->exec("INSERT OR IGNORE INTO full_text2 SELECT * FROM full_text");
            $dbHandle->exec("DROP TABLE full_text");
            $dbHandle->exec("ALTER TABLE full_text2 RENAME TO full_text");
            $dbHandle->commit();

            $dbHandle->exec("VACUUM");
            $dbHandle = null;

            flock($fp, LOCK_UN);
            fclose($fp);

            copy($database_path.'newlibrary.sq3', $database_path.'library.sq3');
            unlink($database_path.'newlibrary.sq3');

        } else {
            fclose($fp);
        }
    }
?>
<html>
    <body>
        <script type="text/javascript">
            top.location='<?php print $url ?>';
        </script>
    </body>
</html>