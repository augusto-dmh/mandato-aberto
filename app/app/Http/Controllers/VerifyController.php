<?php

namespace App\Http\Controllers;

use App\Cards\Code;
use App\Cards\Payloads;
use App\Cards\Share;
use App\Models\CardSnapshot;
use App\Models\House;
use App\Models\Member;
use App\Models\Membership;
use App\Models\RollCall;
use App\Presenters\Labels;
use App\Support\PublicUrl;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * `/verificar/`: a printed code opens exactly what its card showed, and says whether the current data
 * still match (share-cards S5).
 */
class VerifyController extends Controller
{
    private const HOW_A_CODE_LOOKS = 'O código tem oito números, um hífen e oito letras ou números, como 20270930-K7Q29XPD.';

    public function index(Request $request): Response|RedirectResponse
    {
        $typed = trim((string) $request->query('codigo', ''));
        if ($typed !== '') {
            // `away`: the URL generator would trim the canonical trailing slash
            return redirect()->away($request->root().PublicUrl::verify(Code::normalise($typed) ?? rawurlencode($typed)), 302);
        }

        return Inertia::render('Verify/Index', [
            'meta' => [
                'title' => 'Verificar um card',
                'description' => 'Digite o código impresso num card do Mandato Aberto para ver os dados que ele mostrou e se ainda são os atuais.',
                'path' => PublicUrl::verify(),
            ],
            'howACodeLooks' => self::HOW_A_CODE_LOOKS,
        ]);
    }

    public function show(Request $request, string $code): HttpResponse
    {
        $canonical = Code::normalise($code);
        $snapshot = $canonical === null ? null : CardSnapshot::query()->where('code', $canonical)->first();
        if ($snapshot === null) {
            return $this->notFound($request);
        }
        if ($code !== $canonical) {
            return redirect()->away($request->root().PublicUrl::verify($canonical), 301);
        }

        $payload = $snapshot->payload;
        $house = House::from((string) $payload['house']);
        $current = $this->current($payload);
        $subjectPath = Share::subjectPath($payload);

        return Inertia::render('Verify/Show', [
            'meta' => [
                'title' => "Código {$canonical}",
                'description' => 'Os dados que o card de código '.$canonical.' mostrou, '.Labels::ofHouse($house).', e se ainda são os atuais.',
                'path' => PublicUrl::verify($canonical),
                'image' => Share::image($payload, $canonical),
                'noindex' => true,
            ],
            'code' => $canonical,
            // A gone subject's card answers 404 (AC 20): its page shows the values as text only (S5 amendment).
            'image' => $current === null ? null : ['src' => PublicUrl::card($subjectPath, $canonical, '1200x630'), 'alt' => Share::alt($payload, $canonical)],
            'state' => $current === null ? 'gone' : (Code::of($current) === $canonical ? 'equal' : 'changed'),
            'dataDate' => Share::dataDate($payload),
            'subjectUrl' => $subjectPath,
            'collected' => 'Dados abertos '.Labels::ofHouse($house).', coletados em '.Share::dataDate($payload).'.',
            'card' => $this->values($payload),
            'sources' => [],
        ])->toResponse($request);
    }

    /**
     * The subject's payload from the current data, or null when the subject is no longer in it.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function current(array $payload): ?array
    {
        $house = House::from((string) $payload['house']);
        if ($payload['kind'] === 'member') {
            $member = Member::query()->where('house', $house)->where('source_id', $payload['sourceId'])->first();
            $membership = $member?->memberships()->where('legislature_number', $payload['legislature'])->first();

            /** @var Member $member */
            return $membership instanceof Membership ? Payloads::member($house, $member, $membership) : null;
        }
        $rollCall = RollCall::query()->with('proposition')->where('house', $house)->where('source_id', $payload['sourceId'])->first();

        return $rollCall === null ? null : Payloads::rollCall($house, $rollCall);
    }

    /**
     * Every value the card showed, as the page writes it.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function values(array $payload): array
    {
        $house = House::from((string) $payload['house']);
        $values = ['kind' => $payload['kind'], 'house' => $house->value, 'houseName' => Labels::house($house)];
        if ($payload['kind'] === 'member') {
            return [...$values,
                'name' => $payload['name'],
                'party' => $payload['party'],
                'uf' => $payload['uf'],
                'legislature' => $payload['legislature'],
                'photoSha256' => $payload['photoSha256'],
                'figures' => $payload['figures'],
                'votes' => array_map(fn (array $v) => [...$v, 'href' => PublicUrl::rollCall($house, $v['rollCallId'])], $payload['votes']),
            ];
        }

        return [...$values,
            'heading' => $payload['heading'],
            'classification' => Labels::BALLOTS[$payload['ballot']].' · '.Labels::KINDS[$payload['rollCallKind']].' · '.CarbonImmutable::parse((string) $payload['date'])->format('d/m/Y'),
            'result' => Share::result($payload),
            'tallies' => Share::tallies($payload),
            'noTallies' => Share::noTallies($payload),
        ];
    }

    private function notFound(Request $request): HttpResponse
    {
        return Inertia::render('Verify/Show', [
            'meta' => [
                'title' => 'Código não encontrado',
                'description' => self::HOW_A_CODE_LOOKS,
                'path' => PublicUrl::verify(),
                'noindex' => true,
            ],
            'code' => null,
            'howACodeLooks' => self::HOW_A_CODE_LOOKS,
            'verifyUrl' => PublicUrl::verify(),
            'sources' => [],
        ])->toResponse($request)->setStatusCode(404);
    }
}
