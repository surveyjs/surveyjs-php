<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Data\InMemoryDBAdapter;

class ApiController
{

   /**
    * @Route("/api/getActive")
    */
    public function surveys(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse(array_values($db->getSurveys()));
    }
   /**
    * @Route("/api/getSurvey")
    */
    public function getSurvey(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->getSurvey($_GET["surveyId"]));
    }
   /**
    * @Route("/api/create")
    */
    public function create(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->createSurvey());
    }
   /**
    * @Route("/api/delete")
    */
    public function delete(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->deleteSurvey($_GET["id"]));
    }
   /**
    * @Route("/api/changeJson", methods={"POST"})
    */
    public function update(Request $request): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        $parameters = json_decode($request->getContent(), true);
        return new JsonResponse($db->storeSurvey($parameters["id"], $parameters["json"]));
    }
   /**
    * @Route("/api/post", methods={"POST"})
    */
    public function postResults(Request $request): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        $parameters = json_decode($request->getContent(), true);
        return new JsonResponse($db->postResults($parameters["postId"], $parameters["surveyResult"]));
    }
   /**
    * @Route("/api/results")
    */
    public function results(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->getResults($_GET["postId"]));
    }
}