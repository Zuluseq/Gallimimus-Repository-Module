<?php

declare(strict_types=1);

namespace GallimimusRepositoryModule\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;

use function time;

class Get implements RequestHandlerInterface
{
	var $config;
	var $adapter;
	var $sql;

	public function __construct($config,$adapter)
	{
		$this->config = $config;
        $this->adapter = $adapter;
		$this->sql = new Sql($adapter);
	}

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
		$adapter = $this->adapter;
		$body = $request->getParsedBody();
		$params = $request->getQueryParams();
		$attributes = $request->getAttributes();

		$qi = function($name) use ($adapter) { return $adapter->platform->quoteIdentifier($name); };
		$fp = function($name) use ($adapter) { return $adapter->driver->formatParameterName($name); };
		
		$slug = $attributes['slug'];
		if($slug != null) $slug = htmlspecialchars($slug, ENT_HTML5, 'UTF-8');

		// pobieram listę możliwych zapytań do bazy po slugu
		$qsql = "select * from query where slug = '$slug'";
		$qstatement = $adapter->query($qsql);
		$qresults = $qstatement->execute();
		$qresult = array();
		foreach($qresults as $res)
		{
			$qresult[] = $res;
		}

		if(count($qresult)==0) return $this->byk("Zapytanie niedozwolone");

		// sprawdzam, czy uprawnienia się zgadzają
		$rola = $qresult[0]['role'];
		if($rola == 'admin' && $mojarola != 'admin') return $this->byk("nie masz uprawnień admina");
		if($rola == 'customer' && ( $mojarola != 'customer' || $mojarola != 'admin')) return $this->byk("nie masz uprawnień");
		
		// weryfikuje kryteria zapytania
		$sql = $qresult[0]['query'];
		if(count($params)>0)
		{
			foreach($params as $key => $value)
			{
				$value = $this->czyscString($value);
				$sql = str_replace('{'.$key.'}',$value, $sql);
			}
		}

		// zwracam wynik
		$statement = $adapter->query($sql);
		$results = $statement->execute();
		$wynik = array();
		foreach($results as $res)
		{
			$wynik[] = $res;
		}
		if(count($wynik)==0) return $this->empty(204);

        return new JsonResponse(['results' => $wynik[0]['template']]);
    }

	private function czyscString($str) {
		$str = str_replace('\'', '', $str);
		$str = str_replace('\"', '', $str);
		$str = str_replace(';', '', $str);
		$str = str_replace('`', '', $str);
		$str = str_replace('&', '', $str);
		$str = str_replace('{', '', $str);
		$str = str_replace('}', '', $str);
		return $str;
	}

	public function byk($mess)
	{
		return new JsonResponse([
				'ack' => time(),
				'source' => 'Get',
				'status' => "error",
				'message' => $mess,
				]);
	}

	public function empty($status=204)
	{
		// return new EmptyResponse($status,[]);
		$response = new EmptyResponse();
		return $response;
	}

}
