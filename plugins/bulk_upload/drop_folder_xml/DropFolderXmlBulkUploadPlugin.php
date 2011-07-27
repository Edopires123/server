<?php
/**
 * @package plugins.dropFolderXmlBulkUpload
 */
class DropFolderXmlBulkUploadPlugin extends KalturaPlugin implements IKalturaBulkUpload, IKalturaPending, IKalturaSchemaDefiner
{
	const PLUGIN_NAME = 'dropFolderXmlBulkUpload';
	const XML_BULK_UPLOAD_PLUGIN_VERSION_MAJOR = 1;
	const XML_BULK_UPLOAD_PLUGIN_VERSION_MINOR = 1;
	const XML_BULK_UPLOAD_PLUGIN_VERSION_BUILD = 0;
	
	/* (non-PHPdoc)
	 * @see IKalturaPlugin::getPluginName()
	 */
	public static function getPluginName()
	{
		return self::PLUGIN_NAME;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaPending::dependsOn()
	 */
	public static function dependsOn()
	{
		$bulkUploadXmlVersion = new KalturaVersion(
			self::XML_BULK_UPLOAD_PLUGIN_VERSION_MAJOR,
			self::XML_BULK_UPLOAD_PLUGIN_VERSION_MINOR,
			self::XML_BULK_UPLOAD_PLUGIN_VERSION_BUILD);
			
		$bulkUploadXmlDependency = new KalturaDependency(BulkUploadXmlPlugin::getPluginName(), $bulkUploadXmlVersion);
		$dropFolderDependency = new KalturaDependency(DropFolderPlugin::getPluginName());
		
		return array($bulkUploadXmlDependency, $dropFolderDependency);
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaEnumerator::getEnums()
	 */
	public static function getEnums($baseEnumName = null)
	{
		if(is_null($baseEnumName))
			return array('DropFolderXmlBulkUploadType', 'DropFolderXmlFileHandlerType', 'DropFolderXmlBulkUploadErrorCode', 'DropFolderXmlSchemaType');
		
		if($baseEnumName == 'BulkUploadType')
			return array('DropFolderXmlBulkUploadType');
		
		if($baseEnumName == 'DropFolderFileHandlerType')
			return array('DropFolderXmlFileHandlerType');
			
		if($baseEnumName == 'DropFolderFileErrorCode')
			return array('DropFolderXmlBulkUploadErrorCode');
			
		if($baseEnumName == 'SchemaType')
			return array('DropFolderXmlSchemaType');
			
		return array();
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaObjectLoader::loadObject()
	 */
	public static function loadObject($baseClass, $enumValue, array $constructorArgs = null)
	{
		//Gets the right job for the engine	
		if($baseClass == 'kBulkUploadJobData' && $enumValue == self::getBulkUploadTypeCoreValue(DropFolderXmlBulkUploadType::DROP_FOLDER_XML))
			return new kBulkUploadXmlJobData();
		
		 //Gets the right job for the engine	
		if($baseClass == 'KalturaBulkUploadJobData' && $enumValue == self::getBulkUploadTypeCoreValue(DropFolderXmlBulkUploadType::DROP_FOLDER_XML))
			return new KalturaBulkUploadXmlJobData();
		
		//Gets the engine (only for clients)
		if($baseClass == 'KBulkUploadEngine' && class_exists('KalturaClient') && $enumValue == KalturaBulkUploadType::DROP_FOLDER_XML)
		{
			list($taskConfig, $kClient, $job) = $constructorArgs;
			return new DropFolderXmlBulkUploadEngine($taskConfig, $kClient, $job);
		}
		
		if ($baseClass == 'DropFolderFileHandler' && $enumValue == KalturaDropFolderFileHandlerType::XML)
				return new DropFolderXmlBulkUploadFileHandler();
		
		// drop folder does not work in partner services 2 context because it uses dynamic enums
		if (class_exists('kCurrentContext') && kCurrentContext::$ps_vesion == 'ps3')
		{			
			if ($baseClass == 'KalturaDropFolderFileHandlerConfig' && $enumValue == self::getFileHandlerTypeCoreValue(DropFolderXmlFileHandlerType::XML))
				return new KalturaDropFolderXmlBulkUploadFileHandlerConfig();
		}
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaObjectLoader::getObjectClass()
	 */
	public static function getObjectClass($baseClass, $enumValue)
	{
		return null;
	}
	
	/**
	 * Returns the log file for bulk upload job
	 * @param BatchJob $batchJob bulk upload batchjob
	 */
	public static function writeBulkUploadLogFile($batchJob)
	{
		if($batchJob->getJobSubType() != self::getBulkUploadTypeCoreValue(DropFolderXmlBulkUploadType::DROP_FOLDER_XML)){
			return;
		}
		
		$xmlElement = BulkUploadXmlPlugin::getBulkUploadMrssXml($batchJob);
		if(is_null($xmlElement)){
			echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?> <mrss><result>Log file is not ready</result></mrss>";
			kFile::closeDbConnections;
			exit;
		}
		echo $xmlElement->asXML();
		KalturaLog::info($xmlElement->asXML());
		kFile::closeDbConnections;
		exit;
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaBulkUpload::getFileExtension()
	 */
	public static function getFileExtension($enumValue)
	{
		if($enumValue == self::getBulkUploadTypeCoreValue(DropFolderXmlBulkUploadType::DROP_FOLDER_XML))
			return 'xml';
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaSchemaContributor::getPluginSchema()
	 */
	public static function getPluginSchema($type)
	{
		$coreType = kPluginableEnumsManager::apiToCore('SchemaType', $type);
		if($coreType != self::getSchemaTypeCoreValue(DropFolderXmlSchemaType::DROP_FOLDER_XML))
			return null;
			
		$baseXsdElement = BulkUploadXmlPlugin::getPluginSchema(BulkUploadXmlPlugin::getApiValue(XmlSchemaType::BULK_UPLOAD_XML));
			
		$xsd = '<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">';
	
		foreach($baseXsdElement->children('http://www.w3.org/2001/XMLSchema') as $element)
		{
			/* @var $element SimpleXMLElement */
			$xsd .= '
	
	' . $element->asXML();
		}
		
		$xsd .= '
				
	<xs:complexType name="T_dropFolderFileContentResource">
		<xs:choice minOccurs="0" maxOccurs="1">
			<xs:element name="fileSize" type="xs:int" minOccurs="1" maxOccurs="1"/>
			<xs:element name="fileChecksum" minOccurs="1" maxOccurs="1">
				<xs:complexType>
					<xs:simpleContent>
						<xs:extension base="xs:string">
							<xs:attribute name="type" use="optional" default="md5">				
								<xs:simpleType>
									<xs:restriction base="xs:string">
										<xs:enumeration value="md5"/>
										<xs:enumeration value="sha1"/>
									</xs:restriction>
								</xs:simpleType>
							</xs:attribute>
						</xs:extension>
					</xs:simpleContent>
				</xs:complexType>
			</xs:element>
		</xs:choice>
		<xs:attribute name="filePath" type="xs:string" use="required"/>
		<xs:attribute name="dropFolderFileId" type="xs:string" use="optional"/>
	</xs:complexType>

	<xs:element name="dropFolderFileContentResource" type="T_dropFolderFileContentResource" substitutionGroup="contentResource" />
	
	';
		
		$xsd .= '
</xs:schema>';
		
		return new SimpleXMLElement($xsd);
	}
		
	/**
	 * @return int id of dynamic enum in the DB.
	 */
	public static function getBulkUploadTypeCoreValue($valueName)
	{
		$value = self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
		return kPluginableEnumsManager::apiToCore('BulkUploadType', $value);
	}
		
	/**
	 * @return int id of dynamic enum in the DB.
	 */
	public static function getSchemaTypeCoreValue($valueName)
	{
		$value = self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
		return kPluginableEnumsManager::apiToCore('SchemaType', $value);
	}
		
	/**
	 * @return int id of dynamic enum in the DB.
	 */
	public static function getFileHandlerTypeCoreValue($valueName)
	{
		$value = self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
		return kPluginableEnumsManager::apiToCore('DropFolderFileHandlerType', $value);
	}
	
	/**
	 * @return string external API value of dynamic enum.
	 */
	public static function getApiValue($valueName)
	{
		return self::getPluginName() . IKalturaEnumerator::PLUGIN_VALUE_DELIMITER . $valueName;
	}
}
