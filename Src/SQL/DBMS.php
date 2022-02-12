<?php

namespace SearchEngine\Src\SQL;


class DBMS
{
    //SUPPORTED DATABASES

    const MYSQL = "mysql";          //MySQL 3.x/4.x/5.x

    const PostGreSQL = "pgsql";     //PostgreSQL

    const SQLITE = "sqlite";        //SQLite 3 and SQLite 2
    
    const SQLSRV = "sqlsrv";        //Microsoft SQL Server / SQL Azure

    const CUBRID = "cubrid";        //Cubrid

    const DBLIB = "dblib";         //FreeTDS / Microsoft SQL Server / Sybase

    const FIREBIRD = "firebird";    //Firebird

    const IBM = "ibm";              //IBM DB2

    const INFORMIX = "informix";    //IBM Informix Dynamic Server

    const OCI = "oci";              //Oracle Call Interface

    const ODBC = "odbc";            //ODBC v3 (IBM DB2, unixODBC and win32 ODBC)
    
    const _4D = "4d";               //4d

    /**
     * @var array Database drivers that support SAVEPOINT * statements.
     */
    public static $supportedDrivers = array("pgsql", "mysql", "sqlite");


}
