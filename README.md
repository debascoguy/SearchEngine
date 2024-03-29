# SearchEngine
A Php - MySql Search Engine. Considering the challenges in using MySQL/MSSQL/SQL as a back-end for industrial level application search-engine. An optimized techniques and algorithms are developed to boost performance and make SQL databases works like magic for the purpose of searching.

# HOW TO USE
Please see the example in the package after downloading for a fast understanding of how to deploy the library.

# SAME EXAMPLE BELOW

error_reporting(E_ALL ^ E_NOTICE);

include dirname(__FILE__) . DIRECTORY_SEPARATOR . "autoloader.php";

/**
 *  Sample Database From:  
 *  https://github.com/datacharmer/test_db
 * 
 * If using XAMPP and on WINDOWS:
 * 
 * 1. Create an empty database: 'employees'
 * 2. Use the test_db.sql in this repository as I have already helped edit the employees.sql file for ease of import.
 * 3. Then, Open Command Line (Windows)
 * 4. cd c:\xampp\mysql\bin
 * 5. C:\xampp\mysql\bin\> mysql -u {username} -p {databasename} < file_name.sql
 *
 */ 


/** 
 * CREATE MYSQL CONNECTION
 */
$connection = SearchEngine\SQL\PDOConnection::getInstance(new SearchEngine\SQL\ConnectionProperty("localhost", "root", "", "employees"));

/**
 * EXAMPLE 1: fulltext search example: First, run this SQL on your employees Database:
 * 
 * ALTER TABLE employees ADD FULLTEXT(first_name, last_name);
 * =====================================================================================================================
 */

/**     SEARCH STRING : The strings to be search inside the database (Boolean Search).  */
$searchString = "Georgi OR Paddy OR King NOT Gregory";

$SqlLoadDB = new SearchEngine\SQL\SqlLoadDB(
    $connection,
    (new SearchEngine\SQL\QueryBuilder())->setTableName("employees"),
    new SearchEngine\SentenceAnalyzer\MysqlFullText(
        $searchString, 
        ["employees.first_name", "employees.last_name"],
        SearchEngine\SentenceAnalyzer\MysqlFullText::IN_BOOLEAN_MODE
    )
);

//Now, Search
$searchEngine = new SearchEngine\SearchEngine();
$searchEngine->add($SqlLoadDB)
//    Register a callback on the Search Engine before viewing of final results (Optional)...
    ->registerResultCallBack(function ($searchResult) {
        while (count($searchResult) < 4095) {
            //Simply duplicate the search to increase the total number of result... 
           //Just an example of how to add an inline callback.
            $result2 = $searchResult;
            $searchResult = array_merge($searchResult, $result2);
        }
        return $searchResult;
    });

$result = $searchEngine->search()->getResult();


/**
 * EXAMPLE 2 : Test of Using Multiple DataSource By creating another SqlLoadDB()
 * =====================================================================================================================
 */

/**     USING QUERY BUILDER TO BUILD YOUR SEARCH QUERY...   */
$mysqlQueryBuilder = new SearchEngine\SQL\QueryBuilder();
$mysqlQueryBuilder->select(array(
    /** employees.first_name As employeesfirst_name : Use this if duplicate column exist. */
    "employees.first_name" => "First Name",
    "employees.emp_no" => "Employee Number",
    "employees.birth_date",
    "employees.hire_date",
))->from("employees")
    ->leftJoin("dept_emp", "dept_emp.emp_no = employees.emp_no")
    ->where("employees.first_name IN ('Georgi', 'Paddy', 'King')")
    ->andWhere("employees.first_name NOT IN ('Gregory')")

    /** Another Method of Adding Single Column to select() Field-list While also testing the Mysql UNION function */
    ->union()
    ->selectColumn("employees.first_name", "First Name")
    ->selectColumn("employees.emp_no", "Employee Number")
    ->selectColumn("employees.birth_date")
    ->selectColumn("employees.hire_date")
    ->from("employees")
    ->leftJoin("dept_emp", "dept_emp.emp_no = employees.emp_no")
    ->where("employees.first_name IN ('Georgi', 'Paddy', 'King')")
    ->andWhere("employees.first_name NOT IN ('Gregory')");


$SqlLoadDB2 = new SearchEngine\SQL\SqlLoadDB(
    $connection,
    $mysqlQueryBuilder,
    new SearchEngine\SentenceAnalyzer\SqlLike($searchString)
);
//Now, Search
$searchEngine = $searchEngine->reset();
$searchEngine->add($SqlLoadDB2)->registerResultCallBack(function ($searchResult) {
    while (count($searchResult) < 4095) {
        $result2 = $searchResult;
        $searchResult = array_merge($searchResult, $result2);
    }
    return $searchResult;
});
$result2 = $searchEngine->search()->getResult();


/**
 * EXAMPLE 3: File System Searching...
 */
/** @var SearchEngine\FileSystem\SearchOption $fileSystemDataSource */
$fileSystemDataSource = new SearchEngine\FileSystem\SearchOption(
    dirname(__FILE__). DIRECTORY_SEPARATOR . "dictionary",  /** >> This can be either file or directory ==>> */
    "BASIC"
);
$fileSystemDataSource->setGroupResultByFilePath(true);
$fileSystemLoadDB = new SearchEngine\FileSystem\CtrlF($fileSystemDataSource);
//Now, Search
$searchEngine = $searchEngine->reset();
$result3 = $searchEngine->add($fileSystemLoadDB)->search()->getResult();


echo "<h3>EXAMPLE 1 - SEARCH RESULT</h3>";
var_dump($result);
echo "<h3>EXAMPLE 2 - SEARCH RESULT</h3>";
var_dump($result2);
echo "<h3>EXAMPLE 3 - SEARCH RESULT</h3>";
var_dump($result3);

