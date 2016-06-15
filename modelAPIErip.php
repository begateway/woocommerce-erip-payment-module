<?php
	/**
	* 	Класс для работы с API
	*/
	class API
	{
		
		protected $headers = array();

		protected $domainAPI = 'https://api.bepaid.by';

		protected $idShop = null;

		protected $apiKeyShop = null;

		function __construct()
		{
			
		}

		public function setDomainAPI($domainAPI = '')
		{
			$this->domainAPI = 'https://'.$domainAPI;
		}

		public function setIdShop($idShop = '')
		{
			$this->idShop = $idShop;
		}

		public function setApiKeyShop($apiKeyShop = '')
		{
			$this->apiKeyShop = $apiKeyShop;
		}

		public function getIdShop($idShop = '')
		{
			return $this->idShop;
		}

		public function getApiKeyShop($apiKeyShop = '')
		{
			return $this->apiKeyShop;
		}

		/*
			Получение инфомарции о платеже по ID заказа
			@return Object
		*/
		public function getInfoPaymentsWithOrderID($idOrder)
		{
			$headers = array(
	            "Content-Type: application/json",
	            "Content-Length: " . strlen(json_encode($arrayDataInvoice)) 
	        );
			$ch = curl_init($this->domainAPI.'/beyag/payments/?order_id='.$idOrder);
			curl_setopt($ch, CURLOPT_PORT, 443);
		    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		    curl_setopt($ch, CURLOPT_USERPWD, $this->getIdShop().':'.$this->getApiKeyShop() );
			$response = curl_exec($ch);

			if (!$response) {
				curl_close($ch);
				return curl_error($ch);
			} else {
				curl_close($ch);
				return json_decode($response);
			}
		}
	}