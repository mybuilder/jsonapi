<?php

namespace Bolt\Extension\Bolt\JsonApi\Action;

use Bolt\Extension\Bolt\JsonApi\Config\Config;
use Bolt\Extension\Bolt\JsonApi\Converter\Parameter\Type\Fields;
use Bolt\Extension\Bolt\JsonApi\Exception\ApiInvalidRequestException;
use Bolt\Extension\Bolt\JsonApi\Helpers\DataLinks;
use Bolt\Extension\Bolt\JsonApi\Parser\Parser;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Query\Query;

class FetchAction
{
    /** @var Query $query */
    protected $query;

    /** @var Parser $parser */
    protected $parser;

    /** @var DataLinks $dataLinks */
    protected $dataLinks;

    /** @var Config $config */
    protected $config;

    /**
     * FetchAction constructor.
     *
     * @param Query     $query
     * @param Parser    $parser
     * @param DataLinks $dataLinks
     * @param Config    $config
     */
    public function __construct(
        Query $query,
        Parser $parser,
        DataLinks $dataLinks,
        Config $config
    ) {
        $this->query = $query;
        $this->parser = $parser;
        $this->dataLinks = $dataLinks;
        $this->config = $config;
    }

    /**
     * @param $results
     * @param $message
     */
    protected function throwErrorOnNoResults($results, $message)
    {
        if (! $results || count($results) === 0) {
            throw new ApiInvalidRequestException(
                $message
            );
        }
    }

    /**
     * @param $includes
     * @param $results
     * @param $parameters
     */
    protected function fetchIncludes($includes, $results, $parameters)
    {
        $included = [];

        foreach ($includes as $include) {
            //Loop through all results
            if (is_array($results)) {
                foreach ($results as $key => $item) {
                    //Loop through all relationships
                    foreach ($item->getRelation($include) as $related) {
                        $included = $this->storeRelatedContent($parameters, $include, $related, $key);
                    }
                }
                //Must be a single content type
            } elseif ($results instanceof Content) {
                //Loop through all relationships
                foreach ($results->getRelation($include) as $key => $related) {
                    $included = $this->storeRelatedContent($parameters, $include, $related, $key);
                }
            }

        }

        return $included;
    }

    /**
     * @param $parameters
     * @param $include
     * @param $related
     * @param $key
     * @return mixed
     */
    protected function storeRelatedContent($parameters, $include, $related, $key)
    {
        /** @var Fields $fields */
        $fields = $parameters->get('includes')->getFieldsByContentType($include);

        $included[$key] = $this->parser->parseItem($related, $fields);

        return $included;
    }
}
