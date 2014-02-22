<?php

namespace Wikibase\DataModel\Deserializers;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\Exceptions\InvalidAttributeException;
use Deserializers\Exceptions\MissingAttributeException;
use Deserializers\Exceptions\MissingTypeException;
use Deserializers\Exceptions\UnsupportedTypeException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;

/**
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Thomas Pellissier Tanon
 */
class SnakDeserializer implements Deserializer {

	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @param Deserializer $dataValueDeserializer
	 * @param Deserializer $entityIdDeserializer
	 */
	public function __construct( Deserializer $dataValueDeserializer, Deserializer $entityIdDeserializer ) {
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityIdDeserializer = $entityIdDeserializer;
	}

	/**
	 * @see Deserializer::isDeserializerFor
	 *
	 * @param mixed $serialization
	 *
	 * @return boolean
	 */
	public function isDeserializerFor( $serialization ) {
		return $this->hasSnakType( $serialization ) && $this->hasCorrectSnakType( $serialization );
	}

	private function hasSnakType( $serialization ) {
		return is_array( $serialization ) && array_key_exists( 'snaktype', $serialization );
	}

	private function hasCorrectSnakType( $serialization ) {
		return in_array( $serialization['snaktype'], array( 'novalue', 'somevalue', 'value' ) );
	}

	/**
	 * @see Deserializer::deserialize
	 *
	 * @param mixed $serialization
	 *
	 * @return object
	 * @throws DeserializationException
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );
		$this->requireAttribute( $serialization, 'property' );

		return $this->getDeserialized( $serialization );
	}

	private function getDeserialized( array $serialization ) {
		switch ( $serialization['snaktype'] ) {
			case 'value':
				return $this->newValueSnak( $serialization );
			case 'novalue':
				return $this->newNoValueSnak( $serialization );
			case 'somevalue':
				return $this->newSomeValueSnak( $serialization );
		}
	}

	private function newNoValueSnak( array $serialization ) {
		return new PropertyNoValueSnak( $this->deserializePropertyId( $serialization['property'] ) );
	}

	private function newSomeValueSnak( array $serialization ) {
		return new PropertySomeValueSnak( $this->deserializePropertyId( $serialization['property'] ) );
	}

	private function newValueSnak( array $serialization ) {
		$this->requireAttribute( $serialization, 'datavalue' );

		return new PropertyValueSnak(
			$this->deserializePropertyId( $serialization['property'] ),
			$this->dataValueDeserializer->deserialize( $serialization['datavalue'] )
		);
	}

	private function deserializePropertyId( $serialization ) {
		$propertyId = $this->entityIdDeserializer->deserialize( $serialization );

		if ( !( $propertyId instanceof PropertyId ) ) {
			throw new InvalidAttributeException(
				'property',
				$serialization,
				"'$serialization' is not a valid property ID"
			);
		}

		return $propertyId;
	}

	private function assertCanDeserialize( $serialization ) {
		if ( !$this->hasSnakType( $serialization ) ) {
			throw new MissingTypeException();
		}

		if ( !$this->hasCorrectSnakType( $serialization ) ) {
			throw new UnsupportedTypeException( $serialization['snaktype'] );
		}
	}


	protected function requireAttribute( array $array, $attributeName ) {
		if ( !array_key_exists( $attributeName, $array ) ) {
			throw new MissingAttributeException(
				$attributeName
			);
		}
	}
}
