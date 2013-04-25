<?php

namespace Grid\Installer;

/**
 * AbstractPatch
 *
 * @author David Pozsar <david.pozsar@megaweb.hu>
 */
abstract class AbstractPatch implements PatchInterface
{

    /**
     * Module installer
     *
     * @var ModuleInstaller
     */
    protected $installer;

    /**
     * Quote sql-identifier
     *
     * @param   string  $id
     * @return  string
     */
    protected static function quoteIdentifier( $id )
    {
        return '"' . str_replace( '"', '""', $id ) . '"';
    }

    /**
     * Get module-installer
     *
     * @return  ModuleInstaller
     */
    public function getInstaller()
    {
        return $this->installer;
    }

    /**
     * Get patch data
     *
     * @return  PatchData
     */
    public function getPatchData()
    {
        return $this->getInstaller()
                    ->getPatchData();
    }

    /**
     * Get patcher
     *
     * @return  Patcher
     */
    public function getPatcher()
    {
        return $this->getInstaller()
                    ->getPatcher();
    }

    /**
     * Get db
     *
     * @return  PDO
     */
    public function getDb()
    {
        return $this->getPatcher()
                    ->getDb();
    }

    /**
     * Constructor
     *
     * @param   PatchData   $patchData
     * @param   Patcher     $patcher
     */
    public function __construct( ModuleInstaller $installer )
    {
        $this->installer = $installer;
    }

    /**
     * Detect a version is zero (for install & uninstall)
     *
     * @param   string  $version
     * @return  bool
     */
    protected function isZeroVersion( $version )
    {
        return empty( $version ) || 0 === version_compare( $version, '0' );
    }

    /**
     * Detect if two versions are the same
     *
     * @param   string  $from
     * @param   string  $to
     * @return  bool
     */
    protected function isSameVersion( $from, $to )
    {
        return $from == $to || 0 === version_compare( $from, $to );
    }

    /**
     * Execute an sql-query
     *
     * @param   string  $sql
     * @param   array   $params
     * @return  \PDOStatement
     */
    protected function query( $sql, array $params = null )
    {
        $query = $this->getDb()
                      ->prepare( $sql );

        $query->execute( $params );
        return $query;
    }

    /**
     * Select a field from a table
     *
     * @param   array|string    $table
     * @param   string          $column
     * @param   array           $where
     * @return  int
     */
    protected function selectFromTable( $table, $column, array $where = array() )
    {
        $whereSql = '';

        foreach ( $where as $col => $value )
        {
            if ( $whereSql )
            {
                $whereSql .= '
               AND ';
            }

            $whereSql .= static::quoteIdentifier( $col ) . ' = :' . $col;
        }

        $query = $this->query(
            sprintf(
                'SELECT %s FROM %s WHERE %s ORDER BY %s ASC LIMIT 1',
                static::quoteIdentifier( $column ),
                implode( '.', array_map( array( get_called_class(), 'quoteIdentifier' ), (array) $table ) ),
                $whereSql ?: 'TRUE',
                static::quoteIdentifier( $column )
            ),
            $where
        );

        if ( ! $query->rowCount() )
        {
            return null;
        }

        return $query->fetchObject()->$column;
    }

    /**
     * Insert data into table
     *
     * @param   array|string        $table
     * @param   array               $data
     * @param   null|bool|string    $seq
     * @return  int
     */
    protected function insertIntoTable( $table, array $data, $seq = null )
    {
        $table   = (array) $table;
        $columns = '';
        $values  = '';

        foreach ( $data as $field => $value )
        {
            if ( $columns )
            {
                $columns .= ', ';
            }

            if ( $values )
            {
                $values .= ', ';
            }

            $columns .= static::quoteIdentifier( $field );
            $values  .= ':' . $field;
        }

        $query = $this->query(
            sprintf(
                'INSERT INTO %s ( %s ) VALUES ( %s )',
                implode( '.', array_map( array( get_called_class(), 'quoteIdentifier' ), $table ) ),
                $columns,
                $values
            ),
            $data
        );

        if ( $seq )
        {
            if ( true === $seq )
            {
                $seq = implode( '.', $table ) . '_id_seq';
            }

            return $this->getDb()
                        ->lastInsertId( $seq );
        }

        return null;
    }

    /**
     * Run before patching
     *
     * @param   string  $from
     * @param   string  $to
     * @return  void
     */
    public function beforePatch( $from, $to )
    {
        // dummy
    }

    /**
     * Run after patching
     *
     * @param   string  $from
     * @param   string  $to
     * @return  void
     */
    public function afterPatch( $from, $to )
    {
        // dummy
    }

}
