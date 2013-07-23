<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia	                                                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : info@thelia.net                                                      */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      This program is free software; you can redistribute it and/or modify         */
/*      it under the terms of the GNU General Public License as published by         */
/*      the Free Software Foundation; either version 3 of the License                */
/*                                                                                   */
/*      This program is distributed in the hope that it will be useful,              */
/*      but WITHOUT ANY WARRANTY; without even the implied warranty of               */
/*      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the                */
/*      GNU General Public License for more details.                                 */
/*                                                                                   */
/*      You should have received a copy of the GNU General Public License            */
/*	    along with this program. If not, see <http://www.gnu.org/licenses/>.         */
/*                                                                                   */
/*************************************************************************************/

namespace Thelia\Core\HttpFoundation\Session;

use Symfony\Component\HttpFoundation\Session\Session as BaseSession;
use Thelia\Core\Security\User\UserInterface;
use Thelia\Form\BaseForm;
use Thelia\Model\ConfigQuery;
use Thelia\Tools\URL;

class Session extends BaseSession {

	// -- Language ------------------------------------------------------------

    public function getLocale()
    {
        return $this->get("locale", "en_US");
    }

    public function getLang()
    {
        return substr($this->getLocale(), 0, 2);
    }

    // -- Customer user --------------------------------------------------------

    public function setCustomerUser(UserInterface $user)
    {
    	$this->set('customer_user', $user);
    }

    public function getCustomerUser()
    {
    	return $this->get('customer_user');
    }

    public function clearCustomerUser()
    {
    	return $this->remove('customer_user');
    }

    // -- Admin user -----------------------------------------------------------

    public function setAdminUser(UserInterface $user)
    {
    	$this->set('admin_user', $user);
    }

    public function getAdminUser()
    {
    	return $this->get('admin_user');
    }

    public function clearAdminUser()
    {
    	return $this->remove('admin_user');
    }

    // -- Error form -----------------------------------------------------------

    /**
     * @param string $formName the form name
     */
    public function setErrorFormName($formName)
    {
    	$this->set('error_form', $formName);
    }

    public function getErrorFormName()
    {
    	return $this->get('error_form', null);
    }

    public function clearErrorFormName()
    {
    	return $this->remove('error_form');
    }

    // -- Return page ----------------------------------------------------------

    public function setReturnToUrl($url)
    {
    	$this->set('return_to_url', $url);
    }

    /**
     *
     * @return the return-to URL, or the index page if none is defined.
     */
    public function getReturnToUrl()
    {
    	return $this->get('return_to_url', URL::getIndexPage());
    }

}