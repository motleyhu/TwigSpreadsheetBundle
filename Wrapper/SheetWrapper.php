<?php

namespace MewesK\TwigSpreadsheetBundle\Wrapper;

use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\ColumnDimension;
use PhpOffice\PhpSpreadsheet\Worksheet\RowDimension;

/**
 * Class SheetWrapper.
 */
class SheetWrapper extends BaseWrapper
{
    /**
     * @var int
     */
    const COLUMN_DEFAULT = 0;
    /**
     * @var int
     */
    const ROW_DEFAULT = 1;

    /**
     * @var array
     */
    protected $context;
    /**
     * @var \Twig_Environment
     */
    protected $environment;
    /**
     * @var DocumentWrapper
     */
    protected $documentWrapper;

    /**
     * @var null|int
     */
    protected $row;
    /**
     * @var null|int
     */
    protected $column;
    /**
     * @var Worksheet
     */
    protected $object;
    /**
     * @var array
     */
    protected $attributes;
    /**
     * @var array
     */
    protected $mappings;

    /**
     * SheetWrapper constructor.
     *
     * @param array             $context
     * @param \Twig_Environment $environment
     * @param DocumentWrapper   $documentWrapper
     */
    public function __construct(array $context, \Twig_Environment $environment, DocumentWrapper $documentWrapper)
    {
        $this->context = $context;
        $this->environment = $environment;
        $this->documentWrapper = $documentWrapper;

        $this->row = null;
        $this->column = null;

        $this->object = null;
        $this->attributes = [];
        $this->mappings = [];

        $this->initializeMappings();
    }

    /**
     * @param int|string|null $index
     * @param array           $properties
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \RuntimeException
     */
    public function start($index, array $properties = [])
    {
        if (is_int($index) && $index < $this->documentWrapper->getObject()->getSheetCount()) {
            $this->object = $this->documentWrapper->getObject()->setActiveSheetIndex($index);
        } elseif (is_string($index)) {
            if (!$this->documentWrapper->getObject()->sheetNameExists($index)) {
                // create new sheet with a name
                $this->documentWrapper->getObject()->createSheet()->setTitle($index);
            }
            $this->object = $this->documentWrapper->getObject()->setActiveSheetIndexByName($index);
        } else {
            // create new sheet without a name
            $this->documentWrapper->getObject()->createSheet();
            $this->object = $this->documentWrapper->getObject()->setActiveSheetIndex(0);
        }

        $this->attributes['index'] = $index;
        $this->attributes['properties'] = $properties;

        $this->setProperties($properties, $this->mappings);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function end()
    {
        // auto-size columns
        if (
            isset($this->attributes['properties']['columnDimension']) &&
            is_array($this->attributes['properties']['columnDimension'])
        ) {
            /**
             * @var array $columnDimension
             */
            $columnDimension = $this->attributes['properties']['columnDimension'];
            foreach ($columnDimension as $key => $value) {
                if (isset($value['autoSize'])) {
                    if ('default' === $key) {
                        try {
                            $cellIterator = $this->object->getRowIterator()->current()->getCellIterator();
                            $cellIterator->setIterateOnlyExistingCells(true);

                            foreach ($cellIterator as $cell) {
                                $this->object->getColumnDimension($cell->getColumn())->setAutoSize($value['autoSize']);
                            }
                        } catch (Exception $e) {
                            // ignore exceptions thrown when no cells are defined
                        }
                    } else {
                        $this->object->getColumnDimension($key)->setAutoSize($value['autoSize']);
                    }
                }
            }
        }

        $this->object = null;
        $this->attributes = [];
        $this->row = null;
    }

    public function increaseRow()
    {
        $this->row = $this->row === null ? self::ROW_DEFAULT : $this->row + 1;
    }

    public function increaseColumn()
    {
        $this->column = $this->column === null ? self::COLUMN_DEFAULT : $this->column + 1;
    }

    /**
     * @return int|null
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @param int|null $row
     */
    public function setRow($row)
    {
        $this->row = $row;
    }

    /**
     * @return int|null
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @param int|null $column
     */
    public function setColumn($column)
    {
        $this->column = $column;
    }

    /**
     * @return Worksheet
     */
    public function getObject(): Worksheet
    {
        return $this->object;
    }

    /**
     * @param Worksheet $object
     */
    public function setObject(Worksheet $object)
    {
        $this->object = $object;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * @return array
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * @param array $mappings
     */
    public function setMappings(array $mappings)
    {
        $this->mappings = $mappings;
    }

    protected function initializeMappings()
    {
        $this->mappings['autoFilter'] = function ($value) {
            $this->object->setAutoFilter($value);
        };
        $this->mappings['columnDimension']['__multi'] = function ($column = 'default'): ColumnDimension {
            return $column === 'default' ?
                $this->object->getDefaultColumnDimension() :
                $this->object->getColumnDimension($column);
        };
        $this->mappings['columnDimension']['autoSize'] = function ($value, ColumnDimension $object) {
            $object->setAutoSize($value);
        };
        $this->mappings['columnDimension']['collapsed'] = function ($value, ColumnDimension $object) {
            $object->setCollapsed($value);
        };
        $this->mappings['columnDimension']['columnIndex'] = function ($value, ColumnDimension $object) {
            $object->setColumnIndex($value);
        };
        $this->mappings['columnDimension']['outlineLevel'] = function ($value, ColumnDimension $object) {
            $object->setOutlineLevel($value);
        };
        $this->mappings['columnDimension']['visible'] = function ($value, ColumnDimension $object) {
            $object->setVisible($value);
        };
        $this->mappings['columnDimension']['width'] = function ($value, ColumnDimension $object) {
            $object->setWidth($value);
        };
        $this->mappings['columnDimension']['xfIndex'] = function ($value, ColumnDimension $object) {
            $object->setXfIndex($value);
        };
        $this->mappings['pageMargins']['top'] = function ($value) {
            $this->object->getPageMargins()->setTop($value);
        };
        $this->mappings['pageMargins']['bottom'] = function ($value) {
            $this->object->getPageMargins()->setBottom($value);
        };
        $this->mappings['pageMargins']['left'] = function ($value) {
            $this->object->getPageMargins()->setLeft($value);
        };
        $this->mappings['pageMargins']['right'] = function ($value) {
            $this->object->getPageMargins()->setRight($value);
        };
        $this->mappings['pageMargins']['header'] = function ($value) {
            $this->object->getPageMargins()->setHeader($value);
        };
        $this->mappings['pageMargins']['footer'] = function ($value) {
            $this->object->getPageMargins()->setFooter($value);
        };
        $this->mappings['pageSetup']['fitToHeight'] = function ($value) {
            $this->object->getPageSetup()->setFitToHeight($value);
        };
        $this->mappings['pageSetup']['fitToPage'] = function ($value) {
            $this->object->getPageSetup()->setFitToPage($value);
        };
        $this->mappings['pageSetup']['fitToWidth'] = function ($value) {
            $this->object->getPageSetup()->setFitToWidth($value);
        };
        $this->mappings['pageSetup']['horizontalCentered'] = function ($value) {
            $this->object->getPageSetup()->setHorizontalCentered($value);
        };
        $this->mappings['pageSetup']['orientation'] = function ($value) {
            $this->object->getPageSetup()->setOrientation($value);
        };
        $this->mappings['pageSetup']['paperSize'] = function ($value) {
            $this->object->getPageSetup()->setPaperSize($value);
        };
        $this->mappings['pageSetup']['printArea'] = function ($value) {
            $this->object->getPageSetup()->setPrintArea($value);
        };
        $this->mappings['pageSetup']['scale'] = function ($value) {
            $this->object->getPageSetup()->setScale($value);
        };
        $this->mappings['pageSetup']['verticalCentered'] = function ($value) {
            $this->object->getPageSetup()->setVerticalCentered($value);
        };
        $this->mappings['printGridlines'] = function ($value) {
            $this->object->setPrintGridlines($value);
        };
        $this->mappings['protection']['autoFilter'] = function ($value) {
            $this->object->getProtection()->setAutoFilter($value);
        };
        $this->mappings['protection']['deleteColumns'] = function ($value) {
            $this->object->getProtection()->setDeleteColumns($value);
        };
        $this->mappings['protection']['deleteRows'] = function ($value) {
            $this->object->getProtection()->setDeleteRows($value);
        };
        $this->mappings['protection']['formatCells'] = function ($value) {
            $this->object->getProtection()->setFormatCells($value);
        };
        $this->mappings['protection']['formatColumns'] = function ($value) {
            $this->object->getProtection()->setFormatColumns($value);
        };
        $this->mappings['protection']['formatRows'] = function ($value) {
            $this->object->getProtection()->setFormatRows($value);
        };
        $this->mappings['protection']['insertColumns'] = function ($value) {
            $this->object->getProtection()->setInsertColumns($value);
        };
        $this->mappings['protection']['insertHyperlinks'] = function ($value) {
            $this->object->getProtection()->setInsertHyperlinks($value);
        };
        $this->mappings['protection']['insertRows'] = function ($value) {
            $this->object->getProtection()->setInsertRows($value);
        };
        $this->mappings['protection']['objects'] = function ($value) {
            $this->object->getProtection()->setObjects($value);
        };
        $this->mappings['protection']['password'] = function ($value) {
            $this->object->getProtection()->setPassword($value);
        };
        $this->mappings['protection']['pivotTables'] = function ($value) {
            $this->object->getProtection()->setPivotTables($value);
        };
        $this->mappings['protection']['scenarios'] = function ($value) {
            $this->object->getProtection()->setScenarios($value);
        };
        $this->mappings['protection']['selectLockedCells'] = function ($value) {
            $this->object->getProtection()->setSelectLockedCells($value);
        };
        $this->mappings['protection']['selectUnlockedCells'] = function ($value) {
            $this->object->getProtection()->setSelectUnlockedCells($value);
        };
        $this->mappings['protection']['sheet'] = function ($value) {
            $this->object->getProtection()->setSheet($value);
        };
        $this->mappings['protection']['sort'] = function ($value) {
            $this->object->getProtection()->setSort($value);
        };
        $this->mappings['rightToLeft'] = function ($value) {
            $this->object->setRightToLeft($value);
        };
        $this->mappings['rowDimension']['__multi'] = function ($column = 'default'): RowDimension {
            return $column === 'default' ?
                $this->object->getDefaultRowDimension() :
                $this->object->getRowDimension($column);
        };
        $this->mappings['rowDimension']['collapsed'] = function ($value, RowDimension $object) {
            $object->setCollapsed($value);
        };
        $this->mappings['rowDimension']['outlineLevel'] = function ($value, RowDimension $object) {
            $object->setOutlineLevel($value);
        };
        $this->mappings['rowDimension']['rowHeight'] = function ($value, RowDimension $object) {
            $object->setRowHeight($value);
        };
        $this->mappings['rowDimension']['rowIndex'] = function ($value, RowDimension $object) {
            $object->setRowIndex($value);
        };
        $this->mappings['rowDimension']['visible'] = function ($value, RowDimension $object) {
            $object->setVisible($value);
        };
        $this->mappings['rowDimension']['xfIndex'] = function ($value, RowDimension $object) {
            $object->setXfIndex($value);
        };
        $this->mappings['rowDimension']['zeroHeight'] = function ($value, RowDimension $object) {
            $object->setZeroHeight($value);
        };
        $this->mappings['sheetState'] = function ($value) {
            $this->object->setSheetState($value);
        };
        $this->mappings['showGridlines'] = function ($value) {
            $this->object->setShowGridlines($value);
        };
        $this->mappings['tabColor'] = function ($value) {
            $this->object->getTabColor()->setRGB($value);
        };
        $this->mappings['zoomScale'] = function ($value) {
            $this->object->getSheetView()->setZoomScale($value);
        };
    }
}