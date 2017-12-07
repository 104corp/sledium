<?php


namespace Sledium\Handlers;

trait DetermineContentTypeAbleTrait
{
    /** @var string  */
    private $defaultRenderContentType = 'text/html';

    /**
     * @return string
     */
    public function getDefaultRenderContentType(): string
    {
        return $this->defaultRenderContentType;
    }

    /**
     * @param string $defaultRenderContentType
     */
    public function setDefaultRenderContentType(string $defaultRenderContentType)
    {
        $this->defaultRenderContentType = $defaultRenderContentType;
    }

    /**
     * @param string $acceptHeader
     * @param array $knownContentTypes
     * @return string
     */
    protected function determineContentType(
        string $acceptHeader,
        array $knownContentTypes
    ): string {
        if (empty($acceptHeader)) {
            return $this->defaultRenderContentType;
        }
        $selectedContentTypes = array_intersect(
            preg_split('/\s*,\s*/', $acceptHeader, -1, PREG_SPLIT_NO_EMPTY),
            $knownContentTypes
        );
        if (count($selectedContentTypes)) {
            return current($selectedContentTypes);
        }
        foreach ($knownContentTypes as $knownContentType) {
            $contentType = explode('/', $knownContentType);
            $pattern = addslashes($contentType[0]);
            $pattern = isset($contentType[1]) ? $pattern . '/([\w\d]+\+)*' . addslashes($contentType[1]) : '';
            if (preg_match('|' . $pattern . '|i', $acceptHeader)) {
                return $knownContentType;
            }
        }
        return $this->defaultRenderContentType;
    }
}
