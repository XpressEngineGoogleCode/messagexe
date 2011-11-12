<?php
    /**
     * @class  textmessageAdminController
     * @author wiley (wiley@xnurigo.net)
     * @brief  textmessage controller class of textmessage module
     **/

    class textmessageAdminController extends textmessage {
        /**
         * @brief initialization
         * @return none
         **/
        function init() {
			debugPrint('aaaaaaaaaaaaaaaaaaa');
        }

		function procTextmessageAdminSetUser() {
			$this->setMessage("success_updated");
			$redirectUrl = getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTextmessageAdminIndex');
			$this->setRedirectUrl($redirectUrl);
		}
    }
?>
