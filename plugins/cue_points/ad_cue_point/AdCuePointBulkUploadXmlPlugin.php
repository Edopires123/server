<?php
/**
 * Enable ad cue point ingestion from XML bulk upload
 * @package plugins.adCuePoint
 */
class AdCuePointBulkUploadXmlPlugin extends KalturaPlugin implements IKalturaPending, IKalturaSchemaContributor
{
	const PLUGIN_NAME = 'adCuePointBulkUploadXml';
	const BULK_UPLOAD_XML_PLUGIN_NAME = 'bulkUploadXml';
	
	const BULK_UPLOAD_XML_VERSION_MAJOR = 1;
	const BULK_UPLOAD_XML_VERSION_MINOR = 1;
	const BULK_UPLOAD_XML_VERSION_BUILD = 0;

	/* (non-PHPdoc)
	 * @see KalturaPlugin::getInstance()
	 */
	public function getInstance($interface)
	{
		if($this instanceof $interface)
			return $this;
			
		if($interface == 'IKalturaBulkUploadXmlHandler')
			return AdCuePointBulkUploadXmlHandler::get();
			
		return null;
	}
	
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
			self::BULK_UPLOAD_XML_VERSION_MAJOR,
			self::BULK_UPLOAD_XML_VERSION_MINOR,
			self::BULK_UPLOAD_XML_VERSION_BUILD);
			
		$bulkUploadXmlDependency = new KalturaDependency(self::BULK_UPLOAD_XML_PLUGIN_NAME, $bulkUploadXmlVersion);
		$adCuePointDependency = new KalturaDependency(AdCuePointPlugin::getPluginName());
		
		return array($bulkUploadXmlDependency, $adCuePointDependency);
	}
	
	/* (non-PHPdoc)
	 * @see IKalturaSchemaContributor::contributeToSchema()
	 */
	public static function contributeToSchema($type)
	{
		$coreType = kPluginableEnumsManager::apiToCore('SchemaType', $type);
		if($coreType != BulkUploadXmlPlugin::getSchemaTypeCoreValue(XmlSchemaType::BULK_UPLOAD_XML))
			return null;
	
		$xsd = '
		
	<!-- ' . self::getPluginName() . ' -->
	
	<xs:complexType name="T_scene_adCuePointBulkUploadXml">
		<xs:complexContent>
			<xs:extension base="T_scene">
				<xs:sequence>
					<xs:element name="sceneEndTime" minOccurs="0" maxOccurs="1" type="xs:time">
						<xs:annotation>
							<xs:documentation>Cue point end time</xs:documentation>
						</xs:annotation>
					</xs:element>
					<xs:element name="sceneTitle" minOccurs="0" maxOccurs="1">
						<xs:annotation>
							<xs:documentation>Textual title</xs:documentation>
						</xs:annotation>
						<xs:simpleType>
							<xs:restriction base="xs:string">
								<xs:maxLength value="250"/>
							</xs:restriction>
						</xs:simpleType>
					</xs:element>
					<xs:element name="sourceUrl" minOccurs="0" maxOccurs="1" type="xs:string">
						<xs:annotation>
							<xs:documentation>The URL of the ad XML</xs:documentation>
						</xs:annotation>
					</xs:element>
					<xs:element name="adType" minOccurs="1" maxOccurs="1" type="KalturaAdType">
						<xs:annotation>
							<xs:documentation>Indicates the ad type</xs:documentation>
						</xs:annotation>
					</xs:element>
					<xs:element name="protocolType" minOccurs="1" maxOccurs="1" type="KalturaAdProtocolType">
						<xs:annotation>
							<xs:documentation>Indicates the ad protocol type</xs:documentation>
						</xs:annotation>
					</xs:element>
					
					<xs:element ref="scene-extension" minOccurs="0" maxOccurs="unbounded" />
				</xs:sequence>
			</xs:extension>
		</xs:complexContent>
	</xs:complexType>
	
	<xs:element name="scene-ad-cue-point" type="T_scene_adCuePointBulkUploadXml" substitutionGroup="scene">
		<xs:annotation>
			<xs:documentation>Single ad cue point element</xs:documentation>
		</xs:annotation>
	</xs:element>
		';
		
		return $xsd;
	}
}
