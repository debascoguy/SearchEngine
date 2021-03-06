<?php
/**
 * Created By: Ademola Aina
 * Email: debascoguy@gmail.com
 * Date: 4/17/2018
 * Time: 11:32 AM
 */

include dirname(__FILE__).DIRECTORY_SEPARATOR."autoloader.php";


/**
 * CREATE MYSQL CONNECTION
 */
$connection = SearchEngine_Src_MySql_Connection::getInstance(new SearchEngine_Src_MySql_ConnectionProperty("localhost", "root", "", "test_database"));



/**
 * EXAMPLE 1: standard example
 * =====================================================================================================================
 */

/**     SEARCH STRING : The strings to be search inside the database (Boolean Search).  */
$searchString = "debascoguy@yahoo.com OR debascoguy@gmail.com NOT ademola";

$mysqlLoadDB = new SearchEngine_Src_MySql_MySqlLoadDB(
    $connection,
    new SearchEngine_Src_MySql_QueryBuilder(),
    new SearchEngine_Src_SentenceAnalyzer_MysqlFullText(
        $searchString, array("login_session.email", "login_session.username"),
        SearchEngine_Src_SentenceAnalyzer_MysqlFullText::IN_BOOLEAN_MODE
    )
);

//Register a Call Back that will be excuted at the time the Query Results are been fetched directly from the Database (Optional).
$mysqlLoadDB->registerResultCallBack(array($this, 'toString'));

//Now, Search
$searchEngine = new SearchEngine_SearchEngine();
$searchEngine->add($mysqlLoadDB)
//    Register a callback on the Search Engine before viewing of final results (Optional)...
    ->registerResultCallBack(function($searchResult){
//    Duplicate the search to increase the total number of result... Just an example of how to add an inline callback.
    while(count($searchResult) < 4095){
        $result2 = $searchResult;
        $searchResult = array_merge($searchResult, $result2);
    }
    return $searchResult;
});

$result = $searchEngine->search()->getResult();



/**
 * EXAMPLE 2 : Test of Using Multiple DataSource By creating another MysqlLoadDB()
 * =====================================================================================================================
 */

/**     USING QUERY BUILDER TO BUILD YOUR SEARCH QUERY...   */
$mysqlQueryBuilder = new SearchEngine_Src_MySql_QueryBuilder();
$mysqlQueryBuilder->select(array(
    /** login.email As loginEmail : Use this if duplicate column exist. */
    "login.email" => "loginEmail",
    "login.username" => "loginUsername",
    "login_session.email",
    "login_session.username",
))->from("login")
    ->leftJoin("login_session", "login.email = login_session.email")
    ->where("login.email", "IN", "('debascoguy@gmail.com', 'debascoguy@yahoo.com')")

    /** Another Method of Adding Single Column to select() Field-list. While testing the Mysql UNION function */
    ->union()
    ->selectColumn("login.email", "loginEmail")
    ->selectColumn("login.username", "loginUsername")
    ->selectColumn("login_session.email")
    ->selectColumn("login_session.username")
    ->from("login")
    ->leftJoin("login_session", "login.email = login_session.email")
    ->where("login.email", "IN", "('debascoguy@gmail.com', 'debascoguy@yahoo.com')");


$mysqlLoadDB2 = new SearchEngine_Src_MySql_MySqlLoadDB(
    $connection,
    $mysqlQueryBuilder,
    new SearchEngine_Src_SentenceAnalyzer_MysqlLike($searchString)
);
//Now, Search
$searchEngine = $searchEngine->reset();
$searchEngine->add($mysqlLoadDB2)->registerResultCallBack(function($searchResult){
    while(count($searchResult) < 4095){
        $result2 = $searchResult;
        $searchResult = array_merge($searchResult, $result2);
    }
    return $searchResult;
});
$result2 = $searchEngine->search()->getResult();



/**
 * EXAMPLE 3: File System Searching...
 */
/** @var SearchEngine_Src_FileSystem_SearchOption $fileSystemDataSource */
$fileSystemDataSource = new SearchEngine_Src_FileSystem_SearchOption(
    dirname(dirname(__FILE__))."\\Template",    /** >> This can be either file or directory ==>> */
    "toefl"
);
$fileSystemDataSource->setGroupResultByFilePath(true);
$fileSystemLoadDB = new SearchEngine_Src_FileSystem_CtrlF($fileSystemDataSource);
//Now, Search
$searchEngine = $searchEngine->reset();
$result3 = $searchEngine->add($fileSystemLoadDB)->search()->getResult();


echo "<h3>EXAMPLE 1 - SEARCH RESULT</h3>";
var_dump($result);
echo "<h3>EXAMPLE 2 - SEARCH RESULT</h3>";
var_dump($result2);
echo "<h3>EXAMPLE 3 - SEARCH RESULT</h3>";
var_dump($result3);
