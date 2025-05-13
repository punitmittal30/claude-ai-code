<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Block\Adminhtml\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class Form extends Generic
{

    /**
     * @var string
     */
    protected $appDir;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        Context     $context,
        Registry    $registry,
        FormFactory $formFactory,
        array       $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->appDir = $context->getFilesystem()->getDirectoryRead(DirectoryList::APP)->getAbsolutePath();
    }

    /**
     * Get the absolute path to the sample CSV file
     *
     * @return string
     */
    public function getSampleCSV()
    {
        return $this->appDir . 'code/Pratech/ReviewRatings/sample.csv';
    }

    /**
     * Initialize the form
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reviews_importer_form');
        $this->setTitle(__('Reviews Form'));
    }

    /**
     * Build the form elements
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        /**
         * @var \Magento\Framework\Data\Form $form
         */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('review/review/import'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );

        // Add a fieldset for the base configuration
        $fieldsets['base'] = $form->addFieldset('base_fieldset', ['legend' => __('Import CSV File')]);

        // Add a link to download the sample CSV file
        $fieldsets['base']->addField(
            'reviews_importer_note',
            'link',
            [
                'title' => 'Download Sample File',
                'value' => 'Download sample csv format',
                'href' => $this->getUrl('review/*/index/', ['download_sample' => 'yes']),
                'label' => 'CSV Sample'
            ]
        );

        // Add a dropdown for import behavior
        $fieldsets['base']->addField(
            'import_behavior',
            'select',
            [
                'name' => 'import_behavior',
                'label' => __('Import Behavior'),
                'values' => [
                    ['value' => 'add', 'label' => 'Add New Reviews'],
                    ['value' => 'update', 'label' => 'Update Existing Reviews'],
                    ['value' => 'delete', 'label' => 'Delete Reviews by Review ID'],
                ],
                'note' => 'Select the import behavior for reviews',
            ]
        );

        // Add a file input for selecting the CSV file to import
        $fieldsets['base']->addField(
            'reviews_import_file',
            'file',
            [
                'name' => 'reviews_import_file',
                'label' => __('Select File to Import'),
                'title' => __('Select File to Import'),
                'required' => true,
                'class' => 'input-file'
            ]
        );

        // Set the form to use a container
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
