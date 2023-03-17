
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
require __DIR__ . '/../vendor/autoload.php';
$app = AppFactory::create();
$pdo = new PDO('sqlite:bunqdb');
$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("MESSSAGE API");
    return $response;
});
$app->get('/message', function ($request, $response, $args) use ($pdo) {
    header("refresh: 5;");
    $sth = $pdo->prepare("SELECT MESSAGES.*,USERS.NAME FROM MESSAGES JOIN USERS ON USERS.ID=MESSAGES.USERID ORDER BY CREATED_AT DESC");
    $sth->execute();
    $result = $sth->fetchAll();
    if (!empty($result)) {
        $payload = json_encode($result);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    } else {
        $errorArray = array('status' => 'False', 'message' => 'Messages not found');
        $payload = json_encode($errorArray);
        $response->getBody()->write($payload);
        return $response->withHeader("Content-Type", "application/json");
    }
});
$app->post('/message', function ($request, $response, $args) use ($pdo) {
    try {
        $params = $request->getQueryParams();
        if ($params['userid'] && $params['message']) {
            $sth = $pdo->prepare("SELECT * FROM USERS WHERE ID=:userid");
            $status = $sth->execute(array(':userid' => $params['userid']));
            $result = $sth->fetchAll();
            if (!empty($result)) {
                $userid = $params['userid'];
                $message = $params['message'];
                $sth = $pdo->prepare("INSERT INTO MESSAGES (USERID,MESSAGE,CREATED_AT) VALUES (?,?,?)");
                $status = $sth->execute(array($userid, $message, date('Y-m-d H:i:s')));
                if (!empty($status) && $status == 1) {
                    $successArray = array('status' => 'False', 'message' => 'Messages sended successfully');
                    $payload = json_encode($successArray);
                    $response->getBody()->write($payload);
                    return $response->withHeader("Content-Type", "application/json");
                } else {
                    $errorArray = array('status' => 'False', 'message' => 'Messages cant sent');
                    $payload = json_encode($errorArray);
                    $response->getBody()->write($payload);
                    return $response->withHeader("Content-Type", "application/json");
                }
            } else {
                $errorArray = array('status' => 'False', 'message' => 'User not found');
                $payload = json_encode($errorArray);
                $response->getBody()->write($payload);
                return $response->withHeader("Content-Type", "application/json");
            }
        } else {
            $errorArray = array('status' => 'False', 'message' => 'userid and message is required');
            $payload = json_encode($errorArray);
            $response->getBody()->write($payload);
            return $response->withHeader("Content-Type", "application/json");
        }
    }
    catch(PDOException $ex) {
        echo $ex->getMessage();
    }
});
$app->post('/signup', function ($request, $response, $args) use ($pdo) {
    try {
        $params = $request->getQueryParams();
        if ($params['name'] && $params['password']) {
            $name = $params['name'];
            $password = $params['password'];
            $sth = $pdo->prepare("SELECT * FROM USERS WHERE NAME=:name");
            $status = $sth->execute(array(':name' => $name));
            $result = $sth->fetchAll();
            if (!empty($result)) {
                $errorArray = array('status' => 'False', 'message' => 'User exists');
                $payload = json_encode($errorArray);
                $response->getBody()->write($payload);
                return $response->withHeader("Content-Type", "application/json");
            } else {
                $sth = $pdo->prepare("INSERT INTO USERS (NAME,PASSWORD) VALUES (?,?)");
                $status = $sth->execute(array($name, password_hash($password, PASSWORD_DEFAULT)));
                if (!empty($status) && $status == 1) {
                    $successArray = array('status' => 'True', 'message' => 'User added');
                    $payload = json_encode($successArray);
                    $response->getBody()->write($payload);
                    return $response->withHeader("Content-Type", "application/json");
                } else {
                    $errorArray = array('status' => 'True', 'message' => 'User cant add');
                    $payload = json_encode($errorArray);
                    $response->getBody()->write($payload);
                    return $response->withHeader("Content-Type", "application/json");
                }
            }
        } else {
            $errorArray = array('status' => 'False', 'message' => 'name and password is required');
            $payload = json_encode($errorArray);
            $response->getBody()->write($payload);
            return $response->withHeader("Content-Type", "application/json");
        }
    }
    catch(PDOException $ex) {
        echo $ex->getMessage();
    }
});
$app->run();
