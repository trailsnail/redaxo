<?php

rex_extension::register('OUTPUT_FILTER', array('rex_sql_debug', 'doLog'));

/**
 * Class to monitor sql queries
 *
 * @author staabm
 */
class rex_sql_debug extends rex_sql
{
  private static
    $queries = array();

  public function setQuery($qry, $params = array())
  {
    try {
      parent::setQuery($qry, $params);
    } catch (rex_exception $e) {
      $trace = debug_backtrace();
      for( $i=0 ; $trace && $i<sizeof($trace) ; $i++ ) {
          if (isset($trace[$i]['file']) && strpos($trace[$i]['file'], 'sql.php') === false) {
              $file = $trace[$i]['file'];
              $line = $trace[$i]['line'];
              break;
          }
      }
      $firephp = FirePHP::getInstance(true);
      $firephp->error($e->getMessage() .' in ' . $file . ' on line '. $line);
      throw $e; // re-throw exception after logging 
    }
  }
  
  public function execute($params = array())
  {
    $qry = $this->stmt->queryString;

    $timer = new rex_timer();
    $res = parent::execute($params);

    self::$queries[] = array($qry . ' (affected '. $this->getRows() .' rows)', $timer->getFormattedTime(rex_timer::MILLISEC));

    return $res;
  }

  static public function doLog($params)
  {
    if(!empty(self::$queries))
    {
      $firephp = FirePHP::getInstance(true);
      $firephp->group(__CLASS__);
      foreach(self::$queries as $qry)
      {
        $firephp->log('Query: '. $qry[0]. ' ' .$qry[1] . 'ms');
      }
      $firephp->groupEnd();
    }
  }
}