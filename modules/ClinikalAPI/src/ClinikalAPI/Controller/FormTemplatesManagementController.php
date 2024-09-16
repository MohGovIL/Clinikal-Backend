<?php
/**
 * Created by PhpStorm.
 * User: yuriyge
 * Date: 2/24/19
 * Time: 5:59 PM
 */

namespace ClinikalApi\Controller;

use GenericTools\Controller\BaseController;
use GenericTools\Model\LangLanguagesTable;
use GenericTools\Model\Lists;
use GenericTools\Model\ListsTable;
use Interop\Container\ContainerInterface;
use ClinikalAPI\Model\GetTemplatesServiceTable;
use GenericTools\Model\RegistryTable;
use OpenEMR\Common\Acl\AclMain;

class FormTemplatesManagementController extends BaseController

{
    const TITLE = "Templates management";
    const TEMPLATES_LIST = 'clinikal_templates';
    private $forms;
    private $fileds;
    private $serviceTypes;
    private $reasonCodes;
    private $templates;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->container = $container;
        if(!AclMain::aclCheckCore('client_app', 'ManageTemplates')){
            exit('Access denied');
        }
    }

    public function templatesManagementIndexAction()
    {

        $this->loadClientSideForms();
        $this->loadServiceTypes();
        $this->loadtemplates();

        $langCode = ($this->container->get(LangLanguagesTable::class)->getLangCode($_SESSION['language_choice'] ? $_SESSION['language_choice'] : $this->container->get(LangLanguagesTable::class)->getLangIdByGlobals()));
        $data = $this->normalizeDataForTable($this->container->get(GetTemplatesServiceTable::class)->fetchNormalizedData($langCode, ['active' => 1]));
        //$this->die_r($data);

        $parameters = array(
            'title' => xlt(self::TITLE),
            'data'  => $data,
            'forms' => $this->forms,
            'serviceTypes' => $this->serviceTypes,
            'templates' => $this->templates
        );
        $this->layout('clinikalApi/layout/layout');
        return $parameters;
    }

    public function templatesManagementAjaxAction()
    {
        $queryFilters = [];
        if ($this->params()->fromQuery('form_name') && $this->params()->fromQuery('form_name') !== 'all') {
            $queryFilters['form_id'] = $this->params()->fromQuery('form_name');
        }
        if ($this->params()->fromQuery('field_name') && $this->params()->fromQuery('field_name') !== 'all') {
            $queryFilters['form_field'] = $this->params()->fromQuery('field_name');
        }
        if ($this->params()->fromQuery('service_type') && $this->params()->fromQuery('service_type') !== 'all') {
            $queryFilters['service_type'] = $this->params()->fromQuery('service_type');
        }
        if ($this->params()->fromQuery('reason_code') && $this->params()->fromQuery('reason_code') !== 'all') {
            $queryFilters['reason_code'] = $this->params()->fromQuery('reason_code');
        }
        if ($this->params()->fromQuery('template') && $this->params()->fromQuery('template') !== 'all') {
            $queryFilters['message_id'] = $this->params()->fromQuery('template');
        }
        if (!is_null($this->params()->fromQuery('active')) && $this->params()->fromQuery('active') !== 'all' && $this->params()->fromQuery('active') !== '') {
            $queryFilters['active'] = $this->params()->fromQuery('active');
        }

        $langCode = ($this->container->get(LangLanguagesTable::class)->getLangCode($_SESSION['language_choice'] ? $_SESSION['language_choice'] : $this->container->get(LangLanguagesTable::class)->getLangIdByGlobals()));
        $data = $this->normalizeDataForTable($this->container->get(GetTemplatesServiceTable::class)->fetchNormalizedData($langCode, $queryFilters));
        $parms = array('data' => $data);
        return $this->responseWithNoLayout($parms, true);
    }

    public function assignTemplateAction()
    {
        $this->loadClientSideForms();
        $this->loadServiceTypes();
        $this->loadtemplates();

        $parameters = array(
            'title' => xlt(self::TITLE),
            'forms' => $this->forms,
            'serviceTypes' => $this->serviceTypes,
            'templates' => $this->templates,
            'user' => $this->getUserNameById($this->getConnectedUserId())
        );

        if ($this->params()->fromQuery('edit')) {
            $queryFilters = [];
            $queryFilters['form_id'] = $this->params()->fromQuery('form_id');
            $queryFilters['form_field'] = $this->params()->fromQuery('field_id');
            $queryFilters['service_type'] = $this->params()->fromQuery('service_type');
            $queryFilters['reason_code'] = $this->params()->fromQuery('reason_code');
            $queryFilters['message_id'] = $this->params()->fromQuery('template');

            $result = $this->container->get(GetTemplatesServiceTable::class)->get($queryFilters)[0];
            $parameters['record'] = $result;
            $this->loadFormFileds($result['form_id']);
            $this->loadReasonCodes($result['service_type']);
            $parameters['formFiles'] = $this->fileds;
            $parameters['reasonCode'] = $this->reasonCodes;
            $parameters['is_edit'] = true;
        }

        if ($this->params()->fromQuery('success_msg')) {
            $parameters['showSuccessMsg'] = true;
        }

        $this->layout('clinikalApi/layout/layout');
        return $parameters;

    }

    public function loadFiledsAction()
    {
         $form = $this->params()->fromQuery('filter');
        if (empty($form)) {
            throw new \Exception('Missing filter parameter');
        }
        $this->loadFormFileds($form);
        return $this->responseWithNoLayout($this->fileds ? $this->fileds : []);
    }

    public function loadReasonCodesAction()
    {
        $serviceType = $this->params()->fromQuery('filter');
        if (empty($serviceType)) {
            throw new \Exception('Missing filter parameter');
        }
        $this->loadReasonCodes($serviceType);
        $reasonCodes = $this->reasonCodes ? $this->reasonCodes : [];
        $allReasonsOptions = [GetTemplatesServiceTable::ALL_REASON_CODE => xlt(GetTemplatesServiceTable::ALL_REASON_CODE_STRING)];

        return $this->responseWithNoLayout(array_merge( $reasonCodes, $allReasonsOptions));
    }

    /**
     * @return \Zend\Stdlib\ResponseInterface
     */
    public function saveAssignTemplateAction()
    {
        $data = $this->params()->fromPost('data');
        $isEdit = $this->params()->fromPost('is_edit');
        $oldData = $isEdit ? $this->params()->fromPost('old_data') : null;

        if ($this->validateData($data)) {
            $isExistData = $data;
            unset($isExistData['seq'], $isExistData['active']);
            $isExist = !empty($this->container->get(GetTemplatesServiceTable::class)->get($isExistData)) ? true : false;
            if(!$isExist && $isEdit) {
                //delete old record
                $this->container->get(GetTemplatesServiceTable::class)->delete($oldData);
            }
            if($isExist && $isEdit) {
                if($this->primaryKeysNotChanged($data, $oldData)) {
                    $this->container->get(GetTemplatesServiceTable::class)->delete($oldData);
                } else {
                    return $this->responseWithNoLayout('data_conflict', true, 409);
                }
            }
            if ($isExist && !$isEdit) {
                return $this->responseWithNoLayout('data_conflict', true, 409);
            }

            $data['update_by'] = $this->getConnectedUserId();
            $result = $this->container->get(GetTemplatesServiceTable::class)->insert($data);
            return $this->responseWithNoLayout(true, true);

        }
        return $this->responseWithNoLayout(false, 500);
    }

    public function deleteAssignTemplateAction()
    {
        $data = $this->params()->fromPost('data');

        if ($this->validateData($data)) {
            unset($data['seq'], $data['active']);

            $valid = $this->container->get(GetTemplatesServiceTable::class)->delete($data);

            return $this->responseWithNoLayout($valid ? true : false, true);
        }
        return $this->responseWithNoLayout(false, 500);
    }

    public function addEditTemplateAction()
    {
        $templates = $this->container->get(ListsTable::class)->getAllList('clinikal_templates', 'title');;
        $this->layout('clinikalApi/layout/layout');
        return [
            'templates' => $templates,
            'title' => xlt(self::TITLE),
            'showSuccessMsg' => ($this->params()->fromQuery('success_msg')) ? true : false
            ];
    }

     public function saveNewTemplateAction()
     {
         $template = trim($this->params()->fromPost('template'));

         $found = $this->container->get(ListsTable::class)->getListForViewForm(self::TEMPLATES_LIST, false, ['title' => $template], false);
         if (!empty($found)) {
             return $this->responseWithNoLayout('data_conflict', true, 409);
         }

         $listObj = new Lists();
         $listObj->exchangeArray([
            'list_id' => self::TEMPLATES_LIST,
            'option_id' => bin2hex(random_bytes(3)),
            'title' => $template,
            'activity' => 1,
         ]);

         $insert = $this->container->get(ListsTable::class)->insert($listObj);
         return $this->responseWithNoLayout($insert ? true : false);

     }

    public function updateTemplateAction()
    {
        $templateId = $this->params()->fromPost('id');
        $templateText = trim($this->params()->fromPost('text'));
        $activity = $this->params()->fromPost('activity');
        $oldText = trim($this->params()->fromPost('old_text'));
        $oldActivity = $this->params()->fromPost('old_activity');

        if ($templateText !== $oldText) {

            $found = $this->container->get(ListsTable::class)->getListForViewForm(self::TEMPLATES_LIST, false, ['title' => $templateText], false);
            if (!empty($found)) {
                return $this->responseWithNoLayout('data_conflict', true, 409);
            }
        }

        $update = $this->container->get(ListsTable::class)->update(
            ['title' => $templateText, 'activity' => $activity],
            ['list_id' => self::TEMPLATES_LIST, 'option_id' => $templateId]
        );

       if ($activity == 0 && $oldActivity == 1) {
           $this->container->get(GetTemplatesServiceTable::class)->update(['active' => 0], ['message_id' => $templateId]);
       }

        return $this->responseWithNoLayout($update ? true : false);

    }

    public function deleteTemplateAction()
    {
        $templateId = $this->params()->fromPost('id');

        $delete = $this->container->get(ListsTable::class)->delete(
            ['list_id' => self::TEMPLATES_LIST, 'option_id' => $templateId]
        );

        if ($delete) {
            $delete = $this->container->get(GetTemplatesServiceTable::class)->delete(['message_id' => $templateId]);
        }
        return $this->responseWithNoLayout($delete ? true : false);
    }

    private function loadClientSideForms()
    {
        $this->forms = $this->container->get(RegistryTable::class)->getFormsKeyDirectoryValueName(['category' => 'React form']);
    }

    private function loadFormFileds($formId = null)
    {
        $this->fileds = is_null($formId)
            ?
            $this->container->get(ListsTable::class)->getListForViewForm('clinikal_form_fields_templates', true)
            :
            $this->container->get(ListsTable::class)->getListForViewForm('clinikal_form_fields_templates', true, ['notes' => $formId]);
    }

    private function loadServiceTypes()
    {
        $this->serviceTypes = $this->container->get(ListsTable::class)->getListForViewForm('clinikal_service_types', true);
    }

    private function loadReasonCodes($serviceType = null)
    {
        $this->reasonCodes = is_null($serviceType)
            ?
            $this->container->get(ListsTable::class)->getListForViewForm('clinikal_reason_codes', true)
            :
            $this->container->get(ListsTable::class)->getListForViewForm('clinikal_reason_codes', true, ['notes' =>  $serviceType]);
    }

    private function loadtemplates()
    {
        $this->templates = $this->container->get(ListsTable::class)->getListForViewForm('clinikal_templates', true, [], false);
    }

    private function normalizeDataForTable($dbMapping)
    {
        $results = [];
        foreach ($dbMapping as $item) {
            $results[] = [
                $item['form'],
                $item['field'],
                $item['service_type'],
                $item['reason_code'],
                $item['template'],
                (intval($item['active']) === 1) ? "<i style='color: green;font-size: 1.7em;' class='fas fa-check-circle'></i>" : "<i style='color: red;  font-size: 1.7em;' class='fas fa-times-circle'></i>",
                '<button class="btn btn-secondary btn-sm btn-edit" onclick="gotoEdit(' . "'{$item['form_id']}','{$item['field_id']}','{$item['service_type_id']}','{$item['reason_code_id']}','{$item['template_id']}'" . ')">' . xlt('Edit') . '</button>'
            ];
        }
        return $results;
    }



    private function validateData($data)
    {
        $requiredFileds = ['form_id','form_field','service_type','reason_code','message_id','seq','active'];
        foreach ($requiredFileds as $name) {
           if(!isset($data[$name]) || is_null($data[$name]) || $data[$name] == '') {
               return false;
           }
        }
        return true;
    }

    private function primaryKeysNotChanged($data, $oldData)
    {
        $requiredFileds = ['form_id','form_field','service_type','reason_code','message_id'];
        foreach ($requiredFileds as $name) {
            if($data[$name] !== $oldData[$name]) return false;
        }
        return true;
    }

}
