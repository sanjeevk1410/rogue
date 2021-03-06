<?php

namespace Rogue\Http\Controllers;

use Rogue\Models\Signup;
use Rogue\Services\Registrar;
use Rogue\Services\CampaignService;

class CampaignsController extends Controller
{
    /**
     * Registrar instance
     *
     * @var Rogue\Services\Registrar
     */
    protected $registrar;

    /**
     * Phoenix instance
     *
     * @var Rogue\Services\CampaignService
     */
    protected $campaignService;

    /**
     * Constructor
     *
     * @param Rogue\Services\Registrar $registrar
     * @param Rogue\Services\CampaignService $campaignService
     */
    public function __construct(Registrar $registrar, CampaignService $campaignService)
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff');

        $this->registrar = $registrar;
        $this->campaignService = $campaignService;
    }

    /**
     * Show overview of campaigns.
     */
    public function index()
    {
        $ids = $this->campaignService->getCampaignIdsFromSignups();
        $campaigns = $this->campaignService->findAll($ids);
        $campaigns = $this->campaignService->appendPendingCountsToCampaigns($campaigns);

        $causes = $campaigns ? $this->campaignService->groupByCause($campaigns) : null;

        return view('pages.campaign_overview')
            ->with('state', $causes);
    }

    /**
     * Show particular campaign inbox.
     *
     * @param  int $campaignId
     */
    public function showInbox($campaignId)
    {
        $signups = Signup::campaign([$campaignId])->has('pending')->with('pending')->get();

        // Grab the user objects for each signup
        $ids = $signups->pluck('northstar_id')->toArray();
        $users = $this->registrar->findAll($ids)->keyBy('id')->map(function ($user, $key) {
            return $user->toArray();
        });

        // For each pending post, get and include the user
        $signups->each(function ($item) {
            $item->posts = $item->pending;
        });

        // Get the campaign data
        $campaignData = $this->campaignService->find($campaignId);

        return view('pages.campaign_inbox')
            ->with('state', [
                'signups' => $signups,
                'campaign' => $campaignData,
                'users' => $users,
            ]);
    }

    /**
     * Show particular campaign and it's posts.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function showCampaign($id)
    {
        $campaign = $this->campaignService->find($id);
        $totals = $this->campaignService->getPostTotals($campaign);

        return view('pages.campaign_single')
            ->with('state', [
                'campaign' => $campaign,
                'post_totals' => [
                    'accepted_count' => $totals->accepted_count,
                    'pending_count' => $totals->pending_count,
                    'rejected_count' => $totals->rejected_count,
                ],
            ]);
    }
}
