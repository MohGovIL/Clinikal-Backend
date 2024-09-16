<?php
/* +-----------------------------------------------------------------------------+
 * Copyright 2016 matrix israel
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see
 * http://www.gnu.org/licenses/licenses.html#GPL
 *    @author  Oshri Rozmarin <oshri.rozmarin@gmail.com>
 * +------------------------------------------------------------------------------+
 *
 */
namespace ClinikalAPI;

use ClinikalAPI\Model\ListOptions;
use ClinikalAPI\Model\ListOptionsTable;
use ClinikalAPI\Service\ApiBuilder;

use Interop\Container\ContainerInterface;
use Laminas\Db\TableGateway\TableGateway as ZendTableGateway;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\MvcEvent;
use Laminas\Db\ResultSet\ResultSet;

use ClinikalAPI\Model\ClinikalPatientTrackingChanges;
use ClinikalAPI\Model\ClinikalPatientTrackingChangesTable;
use ClinikalAPI\Model\FormContextMap;
use ClinikalAPI\Model\FormContextMapTable;
use ClinikalAPI\Model\GetTemplatesService;
use ClinikalAPI\Model\GetTemplatesServiceTable;

use ClinikalAPI\Model\ManageTemplatesLetters;
use ClinikalAPI\Model\ManageTemplatesLettersTable;


class Module {


    public function getAutoloaderConfig()
    {
        return array(
            'Laminas\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Laminas\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,

                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }


    /**
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                ApiBuilder::class =>  function(ContainerInterface $container) {
                    $model = new ApiBuilder($container);
                    return $model;
                },
                ClinikalPatientTrackingChangesTable::class =>  function(ContainerInterface $container) {
                    $dbAdapter = $container->get(\Laminas\Db\Adapter\Adapter::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new ClinikalPatientTrackingChanges());
                    $tableGateway = new ZendTableGateway('clinikal_patient_tracking_changes', $dbAdapter, null, $resultSetPrototype);
                    $table = new ClinikalPatientTrackingChangesTable($tableGateway);
                    return $table;
                },
                FormContextMapTable::class =>  function(ContainerInterface $container) {
                    $dbAdapter = $container->get(\Laminas\Db\Adapter\Adapter::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new FormContextMap());
                    $tableGateway = new ZendTableGateway('form_context_map', $dbAdapter, null, $resultSetPrototype);
                    $table = new FormContextMapTable($tableGateway);
                    return $table;
                },
                GetTemplatesServiceTable::class =>  function(ContainerInterface $container) {

                    $dbAdapter = $container->get(\Laminas\Db\Adapter\Adapter::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new GetTemplatesService());
                    $tableGateway = new ZendTableGateway('clinikal_templates_map', $dbAdapter, null, $resultSetPrototype);
                    $table = new GetTemplatesServiceTable($tableGateway);
                    return $table;
                },
                /* incomment since 21/07/2020 if there is no bugs delete this
                GetLionicCodesTable::class =>  function(ContainerInterface $container) {
                    $dbAdapter = $container->get(\Laminas\Db\Adapter\Adapter::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new ListOptions());
                    $tableGateway = new ZendTableGateway('list_options', $dbAdapter, null, $resultSetPrototype);
                    $table = new ListOptionsTable($tableGateway);
                    return $table;
                },
                */
                ManageTemplatesLettersTable::class =>  function(ContainerInterface $container) {
                    $dbAdapter = $container->get(\Laminas\Db\Adapter\Adapter::class);
                    $resultSetPrototype = new ResultSet();
                    $resultSetPrototype->setArrayObjectPrototype(new ManageTemplatesLetters());
                    $tableGateway = new ZendTableGateway('manage_templates_letters', $dbAdapter, null, $resultSetPrototype);
                    $table = new ManageTemplatesLettersTable($tableGateway);
                    return $table;
                },
            ),
        );
    }
    /**
     * @param MvcEvent $e
     *
     * Register our event listeners here
     */
    public function onBootstrap(MvcEvent $e)
    {
        // Get application service manager and get instance of event dispatcher
        $serviceManager = $e->getApplication()->getServiceManager();
        $oemrDispatcher = $serviceManager->get(EventDispatcherInterface::class);
        $this->sm = $serviceManager;
        // listen for view events for routes in zend_modules
        $oemrDispatcher->addListener(RestApiCreateEvent::EVENT_HANDLE, [$this, 'initApi']);
    }


    /**
     * @param Adapter $m
     *
     * Generate and register in route api calls
     */
    public function initApi($m)
    {
        $apiBuilder= $this->sm->get(ApiBuilder::class);
        $extend_route_map=$apiBuilder->getApi();
        if (count($extend_route_map) > 0) {
            foreach ($extend_route_map as $route => $action) {
                $m->addToRouteMap($route, $action);
            }
        }


    }

    /**
     * load global variables foe every controllers
     * @param ModuleManager $manager
     */
    public function init(ModuleManager $manager)
    {
        $events = $manager->getEventManager();
        $sharedEvents = $events->getSharedManager();

        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function ($e) {
            $controller = $e->getTarget();
            //$controller->layout()->setVariable('status', null);
            $controller->layout('clinikalApi/layout/layout');

            //global variable of language direction
            $controller->layout()->setVariable('language_direction', $_SESSION['language_direction']);

        }, 100);
    }

}
