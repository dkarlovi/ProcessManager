<?php
/**
 * Process Manager.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2017 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://github.com/dpfaffenbauer/ProcessManager/blob/master/gpl-3.0.txt GNU General Public License version 3 (GPLv3)
 */

namespace ProcessManagerBundle\Controller;

use CoreShop\Bundle\ResourceBundle\Controller\ResourceController;
use ProcessManagerBundle\Model\ExecutableInterface;
use Symfony\Component\HttpFoundation\Request;

class ExecutableController extends ResourceController
{
    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function getConfigAction(Request $request)
    {
        $types = $this->getConfigTypes();

        return $this->viewHandler->handle([
            'types' => array_keys($types)
        ]);
    }

    /**
     * @param Request $request
     * @return mixed|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function runAction(Request $request)
    {
        $exe = $this->repository->find($request->get('id'));

        if (!$exe instanceof ExecutableInterface) {
            return $this->viewHandler->handle([
                'success' => false
            ]);
        }

        $this->get('process_manager.registry.processes')->get($exe->getType())->run($exe);

        return $this->viewHandler->handle([
            'success' => true
        ]);
    }

    /**
     * @return array
     */
    protected function getConfigTypes()
    {
        return $this->getParameter('process_manager.processes');
    }
}