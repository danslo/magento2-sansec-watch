<?php

declare(strict_types=1);

namespace IntegerNet\SansecWatch\Model;

use CuyZ\Valinor\Mapper\MappingError;
use CuyZ\Valinor\Mapper\Source\Exception\InvalidSource;
use IntegerNet\SansecWatch\Mapper\PolicyMapper;
use IntegerNet\SansecWatch\Model\DTO\Policy;
use IntegerNet\SansecWatch\Model\Exception\CouldNotFetchPoliciesException;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function sprintf;

class SansecWatchClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PolicyMapper $policyMapper,
    ) {
    }

    /**
     * @return list<Policy>
     * @throws CouldNotFetchPoliciesException
     */
    public function fetchPolicies(Uuid $uuid): array
    {
        try {
            $responseJson = $this->fetchData($uuid);

            return $this->policyMapper->map($responseJson);
        } catch (InvalidSource $invalidSource) {
            throw CouldNotFetchPoliciesException::fromInvalidSource($invalidSource);
        } catch (MappingError $mappingError) {
            throw CouldNotFetchPoliciesException::fromMappingError($mappingError);
        } catch (TransportExceptionInterface $transportException) {
            throw CouldNotFetchPoliciesException::fromTransportException($transportException);
        } catch (HttpExceptionInterface $httpException) {
            throw CouldNotFetchPoliciesException::fromHttpException($httpException);
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws HttpExceptionInterface
     */
    private function fetchData(Uuid $uuid): string
    {
        $uri     = sprintf('https://sansec.watch/api/magento/%s.json', $uuid->toRfc4122());
        $options = [
            'headers' => [
                'Accept' => 'application/json',
            ],
        ];

        $response = $this->httpClient->request('GET', $uri, $options);

        return $response->getContent();
    }
}
