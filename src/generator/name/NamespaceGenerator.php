<?php
namespace keeko\tools\generator\name;

class NamespaceGenerator extends AbstractNameGenerator {
	
	/**
	 * Returns the namespace for an action
	 *
	 * @return string
	 */
	public function getActionNamespace() {
		return $this->service->getPackageService()->getNamespace() . '\\action';
	}
	
	/**
	 * Returns the namespace for a model action
	 *
	 * @return string
	 */
	public function getModelActionNamespace() {
		return $this->getActionNamespace() . '\\model';
	}
	
	/**
	 * Returns the namespace for a relationship action
	 *
	 * @return string
	 */
	public function getRelationshipActionNamespace() {
		return $this->getActionNamespace() . '\\relationship';
	}
	
	/**
	 * Returns the namespace for a responder
	 * 
	 * @return string
	 */
	public function getResponderNamespace() {
		return $this->service->getPackageService()->getNamespace() . '\\responder';
	}
	
	/**
	 * Returns the namespace for a responder with a given format
	 * 
	 * @param string $format
	 * @return string
	 */
	public function getResponderNamespaceByFormat($format) {
		return $this->getResponderNamespace() . '\\' . strtolower($format);
	}
	
	/**
	 * Returns the namespace for a json responder
	 * 
	 * @return string
	 */
	public function getJsonResponderNamespace() {
		return $this->getResponderNamespaceByFormat('json');
	}
	
	/**
	 * Returns the namespace for a html responder
	 * 
	 * @return string
	 */
	public function getHtmlResponderNamespace() {
		return $this->getResponderNamespaceByFormat('html');
	}
	
	/**
	 * Returns the namespace for a model responder with a given format
	 * 
	 * @param string $format
	 * @return string
	 */
	public function getModelResponderNamespace($format) {
		return $this->getResponderNamespaceByFormat($format) . '\\model';
	}
	
	/**
	 * Returns the namespace for a json model responder
	 * 
	 * @return string
	 */
	public function getJsonModelResponderNamespace() {
		return $this->getJsonResponderNamespace() . '\\model';
	}
	
	/**
	 * Returns the namespace for a html model responder
	 * 
	 * @return string
	 */
	public function getHtmlModelResponderNamespace() {
		return $this->getHtmlResponderNamespace() . '\\model';
	}
	
	/**
	 * Returns the namespace for a responder with a given format
	 * 
	 * @param string $format
	 * @return string
	 */
	public function getRelationshipResponderNamespace($format) {
		return $this->getResponderNamespaceByFormat($format) . '\\relationship';
	}
	
	/**
	 * Returns the namespace for a json relationship responder
	 * 
	 * @return string
	 */
	public function getJsonRelationshipResponderNamespace() {
		return $this->getJsonResponderNamespace() . '\\relationship';
	}
	
	/**
	 * Returns the namespace for a html relationship responder
	 * 
	 * @return string
	 */
	public function getHtmlRelationshipResponderNamespace() {
		return $this->getHtmlResponderNamespace() . '\\relationship';
	}
	
	/**
	 * Returns the namespace for a domain
	 * 
	 * @return string
	 */
	public function getDomainNamespace() {
		return $this->service->getPackageService()->getNamespace() . '\\domain'; 
	}
}
