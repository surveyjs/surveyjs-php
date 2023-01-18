<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use App\Data\InMemoryDBAdapter;


/**
 * @Route("/api", name="api_")
 */
class ApiController extends AbstractController
{

   /**
    * @Route("/getActive", name="get_active", methods={"GET"})
    */
    public function surveys(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse(array_values($db->getSurveys()));
    }
   /**
    * @Route("/getSurvey")
    */
    public function getSurvey(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->getSurvey($_GET["surveyId"]));
    }
   /**
    * @Route("/create")
    */
    public function create(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->createSurvey());
    }
   /**
    * @Route("/delete")
    */
    public function delete(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->deleteSurvey($_GET["id"]));
    }
   /**
    * @Route("/changeJson", methods={"POST"})
    */
    public function update(Request $request): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        $parameters = json_decode($request->getContent(), true);
        return new JsonResponse($db->storeSurvey($parameters["id"], $parameters["json"]));
    }
   /**
    * @Route("/post", methods={"POST"})
    */
    public function postResults(Request $request): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        $parameters = json_decode($request->getContent(), true);
        return new JsonResponse($db->postResults($parameters["postId"], $parameters["surveyResult"]));
    }
   /**
    * @Route("/results")
    */
    public function results(): JsonResponse
    {
        $config = null;
        $db = new InMemoryDBAdapter($config);
        return new JsonResponse($db->getResults($_GET["postId"]));
    }
}