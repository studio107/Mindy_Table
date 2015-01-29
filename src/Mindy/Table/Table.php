<?php
/**
 *
 *
 * All rights reserved.
 *
 * @author Okulov Anton
 * @email qantus@mail.ru
 * @version 1.0
 * @company Studio107
 * @site http://studio107.ru
 * @date 19/12/14 12:38
 */

namespace Mindy\Table;


use Exception;
use Mindy\Helper\Creator;
use Mindy\Helper\Traits\Accessors;
use Mindy\Helper\Traits\Configurator;
use Mindy\Orm\QuerySet;
use Mindy\Pagination\Pagination;
use Mindy\Table\Columns\RawColumn;

abstract class Table
{
    use Configurator, Accessors;

    /**
     * @var bool
     */
    public $enablePagination = true;
    /**
     * @var \Mindy\Orm\Model|\Mindy\Orm\QuerySet|array
     */
    public $data = [];
    /**
     * @var array
     */
    public $html = [];
    /**
     * @var string
     */
    public $template = '<table {html}>{caption}{header}{footer}{body}</table>{pager}';
    /**
     * @var array pagination config
     */
    public $paginationConfig = [];
    /**
     * @var \Mindy\Table\Columns\Column[]
     */
    protected $_columns = null;
    /**
     * @var string Table caption
     */
    public $caption = '';
    /**
     * @var bool
     */
    public $enableHeader = true;
    /**
     * @var bool
     */
    public $enableFooter = false;
    /**
     * @var \Mindy\Pagination\Pagination
     */
    private $_pager;

    public function getColumns()
    {
        return [];
    }

    public function __construct($data, array $config = [])
    {
        $this->data = $data;
        $this->configure($config);
        $this->init();
    }

    public function init()
    {

    }

    public function getInitColumns()
    {
        if (is_null($this->_columns)) {
            $this->_columns = [];

            foreach ($this->getColumns() as $name => $config) {
                if (is_numeric($name)) {
                    if (is_string($config)) {
                        $name = $config;
                        $config = ['class' => RawColumn::className()];
                    }
                } else if (!is_array($config)) {
                    $config = ['class' => $config];
                }

                $this->_columns[$name] = Creator::createObject(array_merge([
                    'name' => $name,
                    'table' => $this,
                ], $config));
            }
        }

        return $this->_columns;
    }


    public function render()
    {
        return strtr($this->template, [
            '{html}' => $this->getHtmlAttributes(),
            '{caption}' => $this->renderCaption(),
            '{header}' => $this->renderHeader(),
            '{footer}' => $this->renderFooter(),
            '{body}' => $this->renderBody(),
            '{pager}' => $this->getPager()
        ]);
    }

    public function renderHeader()
    {
        $header = '';
        if ($this->enableHeader) {
            foreach ($this->getInitColumns() as $column) {
                $header .= $column->renderHeadCell();
            }
            return strtr('<thead><tr>{header}</tr></thead>', [
                '{header}' => $header
            ]);
        }
        return $header;
    }

    public function renderFooter()
    {
        $footer = '';
        if ($this->enableFooter) {
            foreach ($this->getInitColumns() as $column) {
                $footer .= $column->renderFootCell();
            }
            return strtr('<tfoot><tr>{footer}</tr></tfoot>', [
                '{footer}' => $footer
            ]);
        }
        return $footer;
    }

    public function renderBody()
    {
        $body = '';
        $data = $this->getData();
        foreach ($data as $item) {
            $row = '';
            foreach ($this->getInitColumns() as $column) {
                $row .= $column->renderCell($item);
            }
            $body .= strtr('<tr {html}>{row}</tr>', [
                '{html}' => $this->getRowHtmlAttributes($item),
                '{row}' => $row
            ]);
        }
        return strtr('<tbody>{body}</tbody>', [
            '{body}' => $body
        ]);
    }

    public function renderCaption()
    {
        if ($this->caption) {
            return strtr('<caption>{caption}</caption>', [
                '{caption}' => $this->caption
            ]);
        }
        return '';
    }

    public function getHtmlAttributes()
    {
        if (is_string($this->html)) {
            return $this->html;
        } else if (is_array($this->html)) {
            $html = '';
            foreach ($this->html as $name => $value) {
                $html .= is_numeric($name) ? " $value" : " $name='$value'";
            }
            return $html;
        }
    }

    public function __toString()
    {
        return (string)$this->render();
    }

    /**
     * @param $record
     * @return string
     */
    public function getRowHtmlAttributes($record)
    {
        return '';
    }

    public function getPager()
    {
        if ($this->_pager === null) {
            $this->_pager = new Pagination($this->data, $this->paginationConfig);
        }

        return $this->_pager;
    }

    public function getData()
    {
        if ($this->enablePagination) {
            return $this->getPager()->paginate();
        } else {
            return is_a($this->data, QuerySet::className()) ? $this->data->all() : $this->data;
        }
    }
}