<?php

declare(strict_types=1);

namespace Common\Actions;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    protected LoggerInterface $logger;

    protected Request $request;

    protected Response $response;

    protected array $args;

    protected int $total = 0;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return array|object
     */
    protected function getFormData()
    {
        return $this->request->getParsedBody();
    }

    /**
     * @return array|*
     */
    protected function getColumns(array $replace = []) {
        $return = ['*'];
        $qparams = $this->request->getQueryParams();
        if(array_key_exists('columns', $qparams)) {
          $return = explode(',', str_replace(array_keys($replace), array_values($replace), $qparams['columns']));
        }
        return array_merge($return, $replace);
    }

    /**
     * @return array|string
     */
    protected function getSearchColumns() {
        $return = '';
        $qparams = $this->request->getQueryParams();
        if(array_key_exists('scolumns',$qparams)) {
          $return = explode(',', $qparams['scolumns']);
        }
        return $return;
    }

    /**
     * @return string
     */
    protected function getSearchQuery() {
        $query = '';
        $qparams = $this->request->getQueryParams();
        if(array_key_exists('search', $qparams)) {
            $query = str_replace(",", " LIKE '%$qparams[search]%' OR ", $qparams['scolumns']);
            $query.= " LIKE '%".$qparams['search']."%'";
        }
        return $query;
    }

    /**
     * @return string
     */
    protected function getOrderBy(string $default = 'id') {
        $qparams = $this->request->getQueryParams();
        return array_key_exists('order', $qparams) ? $qparams['order'] : $default;
    }

    /**
     * @return string
     */
    protected function getOrderByDir(string $dir = 'DESC') {
        $qparams = $this->request->getQueryParams();
        return array_key_exists('dir', $qparams) ? $qparams['dir'] : $dir;
    }

     /**
     * @return int
     */
    protected function getPage(int $offset = 1) {
        $qparams = $this->request->getQueryParams();
        return array_key_exists('offset', $qparams) ? (int) $qparams['offset'] + 1 : $offset;
    }

    /**
     * @return int
     */
    protected function getPageLimit(int $limit = 20) {
        $qparams = $this->request->getQueryParams();
        return array_key_exists('limit', $qparams) ? (int) $qparams['limit'] : $limit;
    }

    protected function setTotal(int $total) {
        $this->total = $total;
    }

    /**
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * Convert array to JSON Object
     * @return array $data
     */
    protected function resolveArray(array $data, array $keys) {
        foreach ($keys as $key) {
            if(array_key_exists($key, $data))
                $data[$key] = $this->resolveJSON($data[$key]);
        }
        return $data;
    }

    /**
     * Convert JSON string to JSON Object
     * @param array|string $data
     * @return JSON
     */
    protected function resolveJSON($data) {
        if(is_array($data))
            return json_encode($data, JSON_FORCE_OBJECT);
        return json_decode($data, true);
    }

    /**
     * @param array|object|null $data
     */
    protected function respondWithData($data = null, int $statusCode = 200): Response
    {

        $payload = new ActionPayload($statusCode, $data);
        $payload->setTotal($this->total);
        return $this->respond($payload);
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        $this->response->getBody()->write($json);

        return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus($payload->getStatusCode());
    }
}
