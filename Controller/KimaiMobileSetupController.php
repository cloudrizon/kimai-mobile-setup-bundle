<?php

namespace KimaiPlugin\KimaiMobileSetupBundle\Controller;

use App\Entity\AccessToken;
use App\Entity\User;
use App\Controller\AbstractController;
use App\Form\AccessTokenForm;
use App\Form\UserApiPasswordType;
use App\Repository\AccessTokenRepository;
use App\User\UserService;
use App\Utils\PageSetup;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/kimai-mobile-setup')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class KimaiMobileSetupController extends AbstractController
{
    public function __construct()   
    {
    }

    private function getPageSetup(User $profile, string $view): PageSetup
    {
        $page = new PageSetup('users');
        $page->setHelp('users.html');
        $page->setActionName('user');
        $page->setActionView($view);
        $page->setActionPayload(['user' => $profile]);

        return $page;
    }

    #[Route(path: '', name: 'kimai_mobile_setup', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        return $this->redirectToRoute('user_profile_api_token_qr', ['username' => $this->getUser()->getUserIdentifier()]);
    }

    #[Route(path: '/{username}/api-token', name: 'user_profile_api_token_qr', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('api-token', 'profile')]
    public function apiTokenAction(
        #[MapEntity(mapping: ['username' => 'username'])]
        User $profile,
        Request $request,
        UserService $userService,
        AccessTokenRepository $accessTokenRepository
    ): Response
    {
        $form = $this->createForm(UserApiPasswordType::class, $profile, [
            'action' => $this->generateUrl('user_profile_api_token_qr', ['username' => $profile->getUserIdentifier()]),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            @trigger_error('User ' . $profile->getUsername() . ' created deprecated API password.', E_USER_DEPRECATED);

            $userService->saveUser($profile);

            $this->flashSuccess('action.update.success');

            return $this->redirectToRoute('user_profile_api_token_qr', ['username' => $profile->getUserIdentifier()]);
        }

        $accessTokens = $accessTokenRepository->findForUser($profile);

        $createdToken = null;

        if (!$request->query->has('hide-token')) {
            $createdId = $request->getSession()->get('_show_access_token');
            $request->getSession()->remove('_show_access_token');
            if ($createdId !== null) {
                foreach ($accessTokens as $accessToken) {
                    if ($accessToken->getId() === $createdId) {
                        $createdToken = $accessToken;
                    }
                }
            }
        }

        $qrCode = null;

        if ($createdToken !== null) {
            $payload = [
                'serverURL' => $request->getSchemeAndHttpHost(),
                'userEmail' => $profile->getEmail(),
                'apiToken' => $createdToken->getToken(),
            ];

            $qrCode = Builder::create()
                ->writer(new PngWriter())
                ->writerOptions([])
                ->data(json_encode($payload))
                ->encoding(new Encoding('UTF-8'))
                ->errorCorrectionLevel(new ErrorCorrectionLevelHigh())
                ->size(200)
                ->margin(0)
                ->roundBlockSizeMode(new RoundBlockSizeModeMargin())
                ->build();
        }

        return $this->render('@KimaiMobileSetup/api-token.html.twig', [
            'tab' => 'api-token',
            'created_token' => $createdToken,
            'access_tokens' => $accessTokens,
            'page_setup' => $this->getPageSetup($profile, 'api-token'),
            'user' => $profile,
            'form' => $form->createView(),
            'qr_code' => $qrCode,
        ]);
    }

    #[Route(path: '/{username}/create-access-token', name: 'user_profile_access_token_qr', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    #[IsGranted('api-token', 'profile')]
    public function createAccessToken(
        #[MapEntity(mapping: ['username' => 'username'])]
        User $profile,
        Request $request,
        AccessTokenRepository $accessTokenRepository
    ): Response
    {
        $accessToken = new AccessToken($profile, substr(bin2hex(random_bytes(100)), 0, 25));

        $form = $this->createForm(AccessTokenForm::class, $accessToken, [
            'action' => $this->generateUrl('user_profile_access_token_qr', ['username' => $profile->getUserIdentifier()]),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $accessTokenRepository->saveAccessToken($accessToken);

            $this->flashSuccess('action.update.success');
            $request->getSession()->set('_show_access_token', $accessToken->getId());

            return $this->redirectToRoute('user_profile_api_token_qr', ['username' => $profile->getUserIdentifier()]);
        }

        return $this->render('@KimaiMobileSetup/access-token.html.twig', [
            'access_token' => $accessToken,
            'user' => $profile,
            'form' => $form->createView(),
        ]);
    }
}
