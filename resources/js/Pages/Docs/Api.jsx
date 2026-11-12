import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import { Check, Copy, KeyRound, MapPin, Gauge, AlertTriangle, History, Play, BookOpen, Menu, X } from 'lucide-react';

const json = (value) => JSON.stringify(value, null, 2);

function CopyButton({ text, label = 'Salin' }) {
  const [copied, setCopied] = useState(false);

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(text);
      setCopied(true);
      setTimeout(() => setCopied(false), 1800);
    } catch {
      setCopied(false);
    }
  };

  return (
    <button
      type="button"
      onClick={copy}
      className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-gray-300 hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400"
      aria-label={copied ? 'Tersalin' : label}
    >
      {copied ? <Check className="h-3.5 w-3.5 text-primary-300" /> : <Copy className="h-3.5 w-3.5" />}
      {copied ? 'Tersalin' : label}
    </button>
  );
}

function CodeBlock({ code, title }) {
  return (
    <div className="rounded-xl bg-[#0f1f1a] ring-1 ring-black/5 overflow-hidden">
      <div className="flex items-center justify-between px-4 py-1.5 border-b border-white/10">
        <span className="text-xs font-medium text-gray-400">{title}</span>
        <CopyButton text={code} />
      </div>
      <pre className="overflow-x-auto px-4 py-3 text-[13px] leading-relaxed text-gray-100"><code>{code}</code></pre>
    </div>
  );
}

function CodeTabs({ samples }) {
  const [active, setActive] = useState(samples[0].label);
  const current = samples.find((s) => s.label === active) ?? samples[0];

  return (
    <div className="rounded-xl bg-[#0f1f1a] ring-1 ring-black/5 overflow-hidden">
      <div className="flex items-center justify-between border-b border-white/10 pr-2">
        <div role="tablist" className="flex min-w-0 overflow-x-auto">
          {samples.map((sample) => (
            <button
              key={sample.label}
              type="button"
              role="tab"
              aria-selected={sample.label === current.label}
              onClick={() => setActive(sample.label)}
              className={`shrink-0 whitespace-nowrap px-3 sm:px-4 py-2 text-xs font-semibold border-b-2 transition-colors ${
                sample.label === current.label
                  ? 'border-primary-400 text-white'
                  : 'border-transparent text-gray-400 hover:text-gray-200'
              }`}
            >
              {sample.label}
            </button>
          ))}
        </div>
        <CopyButton text={current.code} />
      </div>
      <pre className="overflow-x-auto px-4 py-3 text-[13px] leading-relaxed text-gray-100"><code>{current.code}</code></pre>
    </div>
  );
}

function MethodBadge({ method }) {
  return (
    <span className="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs font-bold tracking-wide text-primary-800">
      {method}
    </span>
  );
}

function Section({ id, title, icon: Icon, children }) {
  return (
    <section id={id} className="scroll-mt-24 border-t border-gray-200 pt-10 first:border-0 first:pt-0">
      <h2 className="flex items-center gap-2 font-display text-2xl font-bold tracking-tight text-gray-900">
        {Icon && <Icon className="h-6 w-6 text-primary-600" aria-hidden="true" />}
        {title}
      </h2>
      <div className="mt-4 space-y-4 text-[15px] leading-7 text-gray-700">{children}</div>
    </section>
  );
}

function Table({ head, rows }) {
  return (
    <div className="overflow-x-auto rounded-xl ring-1 ring-gray-200">
      <table className="min-w-full divide-y divide-gray-200 text-sm">
        <thead className="bg-gray-50">
          <tr>
            {head.map((h) => (
              <th key={h} scope="col" className="px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-gray-500">{h}</th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100 bg-white">
          {rows.map((cells, i) => (
            <tr key={i}>
              {cells.map((cell, j) => (
                <td key={j} className="px-4 py-2.5 align-top text-gray-700">{cell}</td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function exampleQuery(endpoint) {
  const names = endpoint.params.map((p) => p.name);
  if (names.includes('since')) return '?since=2026-10-31T00:00:00Z';
  if (names.includes('tahun')) return '?tahun=2026&per_page=100';
  return '';
}

function samplesFor(baseUrl, endpoint) {
  const url = `${baseUrl}${endpoint.path}${exampleQuery(endpoint)}`;
  return [
    {
      label: 'cURL',
      code: `curl "${url}" \\\n  -H "Authorization: Bearer $SMARTHUT_TOKEN"`,
    },
    {
      label: 'JavaScript',
      code: `const res = await fetch('${url}', {\n  headers: { Authorization: \`Bearer \${process.env.SMARTHUT_TOKEN}\` },\n});\nif (!res.ok) throw new Error(\`SMART-HUT \${res.status}\`);\nconst { data } = await res.json();`,
    },
    {
      label: 'PHP (Laravel)',
      code: `$response = Http::withToken(env('SMARTHUT_TOKEN'))\n    ->acceptJson()\n    ->get('${url}')\n    ->throw();\n\n$data = $response->json('data');`,
    },
  ];
}

function EndpointCard({ endpoint, baseUrl, abilityLabels }) {
  return (
    <article id={`endpoint-${endpoint.route}`} className="scroll-mt-24 rounded-2xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-gray-200 space-y-5">
      <header>
        <div className="flex flex-wrap items-center gap-2">
          <MethodBadge method={endpoint.method} />
          <code className="text-base font-semibold text-gray-900 break-all">/api/v1{endpoint.path}</code>
        </div>
        <h3 className="mt-2 font-display text-lg font-bold text-gray-900">{endpoint.title}</h3>
        <p className="mt-1 text-gray-600">{endpoint.description}</p>
        <p className="mt-2 text-sm">
          <span className="font-semibold text-gray-700">Akses data: </span>
          {endpoint.ability ? (
            <span>
              <code className="rounded bg-primary-50 px-1.5 py-0.5 text-primary-800">{endpoint.ability}</code>
              <span className="text-gray-500"> ({abilityLabels[endpoint.ability]})</span>
            </span>
          ) : (
            <span className="text-gray-600">ability apa pun</span>
          )}
        </p>
      </header>

      {endpoint.params.length > 0 && (
        <div>
          <h4 className="mb-2 text-sm font-bold text-gray-900">Parameter query</h4>
          <Table
            head={['Nama', 'Tipe', 'Wajib', 'Keterangan']}
            rows={endpoint.params.map((p) => [
              <code key="n" className="text-gray-900">{p.name}</code>,
              <span key="t" className="text-gray-500">{p.type}</span>,
              p.required ? 'Ya' : 'Tidak',
              p.description,
            ])}
          />
        </div>
      )}

      <div>
        <h4 className="mb-2 text-sm font-bold text-gray-900">Contoh permintaan</h4>
        <CodeTabs samples={samplesFor(baseUrl, endpoint)} />
      </div>

      <div>
        <h4 className="mb-2 text-sm font-bold text-gray-900">Contoh respons <span className="font-normal text-gray-500">200 OK</span></h4>
        <CodeBlock title="application/json" code={json(endpoint.example)} />
      </div>

      {endpoint.fields.length > 0 && (
        <div>
          <h4 className="mb-2 text-sm font-bold text-gray-900">Field respons</h4>
          <Table
            head={['Field', 'Tipe', 'Keterangan']}
            rows={endpoint.fields.map((f) => [
              <code key="n" className="whitespace-nowrap text-gray-900">{f.name}</code>,
              <span key="t" className="whitespace-nowrap text-gray-500">{f.type}</span>,
              f.description,
            ])}
          />
        </div>
      )}
    </article>
  );
}

function TryIt({ baseUrl }) {
  const [token, setToken] = useState('');
  const [result, setResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const send = async (e) => {
    e.preventDefault();
    setLoading(true);
    const started = performance.now();
    try {
      const res = await fetch(`${baseUrl}/klien`, {
        headers: { Authorization: `Bearer ${token.trim()}`, Accept: 'application/json' },
        credentials: 'omit',
      });
      const body = await res.text();
      let pretty = body;
      try {
        pretty = json(JSON.parse(body));
      } catch {
        // bukan JSON, tampilkan apa adanya
      }
      setResult({
        status: res.status,
        ok: res.ok,
        ms: Math.round(performance.now() - started),
        remaining: res.headers.get('X-RateLimit-Remaining'),
        body: pretty,
      });
    } catch (error) {
      setResult({ status: 0, ok: false, ms: null, body: String(error) });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="rounded-2xl bg-white p-5 sm:p-6 shadow-sm ring-1 ring-gray-200 space-y-4">
      <form onSubmit={send} className="space-y-3">
        <label htmlFor="try-token" className="block text-sm font-semibold text-gray-900">Token</label>
        <div className="flex flex-col sm:flex-row gap-2">
          <input
            id="try-token"
            type="password"
            autoComplete="off"
            spellCheck="false"
            value={token}
            onChange={(e) => setToken(e.target.value)}
            placeholder="1|xxxxxxxxxxxxxxxx"
            className="min-w-0 flex-1 rounded-xl border-gray-300 bg-gray-50 font-mono text-sm focus:bg-white focus:border-primary-600 focus:ring-primary-600"
          />
          <button
            type="submit"
            disabled={loading || !token.trim()}
            className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-800 disabled:cursor-not-allowed disabled:opacity-50"
          >
            <Play className="h-4 w-4" aria-hidden="true" />
            {loading ? 'Mengirim…' : 'Kirim GET /klien'}
          </button>
        </div>
        <p className="text-xs text-gray-500">
          Token hanya dipakai untuk permintaan ini dari browser Anda dan tidak disimpan. Permintaan uji ikut tercatat di log akses dan dihitung dalam rate limit.
        </p>
      </form>

      {result && (
        <div aria-live="polite">
          <div className="mb-2 flex flex-wrap items-center gap-3 text-sm">
            <span className={`inline-flex rounded-md px-2 py-0.5 font-bold ${result.ok ? 'bg-primary-100 text-primary-800' : 'bg-red-100 text-red-700'}`}>
              {result.status || 'Gagal'}
            </span>
            {result.ms !== null && <span className="text-gray-500">{result.ms} ms</span>}
            {result.remaining !== null && result.remaining !== undefined && (
              <span className="text-gray-500">Sisa kuota menit ini: {result.remaining}</span>
            )}
          </div>
          <CodeBlock title="Respons" code={result.body} />
        </div>
      )}
    </div>
  );
}

export default function Api({ version, baseUrl, endpoints, errors, changelog, abilities, rateLimitPerMinute }) {
  const { auth } = usePage().props;
  const [activeId, setActiveId] = useState('mulai');
  const [navOpen, setNavOpen] = useState(false);
  const abilityLabels = useMemo(() => Object.fromEntries(abilities.map((a) => [a.value, a.label])), [abilities]);

  const groups = useMemo(() => {
    const map = new Map();
    endpoints.forEach((e) => map.set(e.group, [...(map.get(e.group) ?? []), e]));
    return [...map.entries()].map(([name, items], i) => ({ id: `grup-${i}`, name, items }));
  }, [endpoints]);

  const nav = [
    { id: 'mulai', label: 'Mulai cepat' },
    { id: 'autentikasi', label: 'Autentikasi' },
    { id: 'cakupan', label: 'Cakupan data' },
    { id: 'akses', label: 'Akses data' },
    { id: 'sinkronisasi', label: 'Pola sinkronisasi' },
    { id: 'endpoint', label: 'Endpoint', children: groups.map((g) => ({ id: g.id, label: g.name })) },
    { id: 'coba', label: 'Coba langsung' },
    { id: 'rate-limit', label: 'Rate limit' },
    { id: 'error', label: 'Kode error' },
    { id: 'versi', label: 'Riwayat versi' },
  ];

  // Konten dirender React setelah halaman dimuat, jadi tautan #anchor perlu digulir manual.
  useEffect(() => {
    const id = decodeURIComponent(window.location.hash.slice(1));
    if (id) document.getElementById(id)?.scrollIntoView();
  }, []);

  useEffect(() => {
    const elements = nav
      .flatMap((n) => [n.id, ...(n.children ?? []).map((c) => c.id)])
      .map((id) => document.getElementById(id))
      .filter(Boolean);

    // Bagian aktif = bagian terakhir yang judulnya sudah melewati header.
    const update = () => {
      const atBottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;
      const passed = elements.filter((el) => el.getBoundingClientRect().top <= 120);
      const current = atBottom ? elements[elements.length - 1] : passed[passed.length - 1] ?? elements[0];
      if (current) setActiveId(current.id);
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    return () => window.removeEventListener('scroll', update);
  }, [endpoints]);

  const navLinks = (
    <ul className="space-y-1 text-sm">
      {nav.map((item) => (
        <li key={item.id}>
          <a
            href={`#${item.id}`}
            onClick={() => setNavOpen(false)}
            className={`block rounded-lg px-3 py-1.5 transition-colors ${
              activeId === item.id ? 'bg-primary-50 font-semibold text-primary-800' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
            }`}
          >
            {item.label}
          </a>
          {item.children && (
            <ul className="mt-1 ml-3 space-y-1 border-l border-gray-200 pl-2">
              {item.children.map((child) => (
                <li key={child.id}>
                  <a
                    href={`#${child.id}`}
                    onClick={() => setNavOpen(false)}
                    className={`block rounded-md px-2 py-1 text-xs transition-colors ${
                      activeId === child.id ? 'text-primary-800 font-semibold' : 'text-gray-500 hover:text-gray-900'
                    }`}
                  >
                    {child.label}
                  </a>
                </li>
              ))}
            </ul>
          )}
        </li>
      ))}
    </ul>
  );

  return (
    <div className="min-h-screen bg-[#fbfcf9] font-sans text-gray-900">
      <Head title="Dokumentasi API" />

      <header className="sticky top-0 z-30 border-b border-gray-200 bg-white/90 backdrop-blur">
        <div className="mx-auto flex h-16 max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
          <button
            type="button"
            className="lg:hidden -ml-1 rounded-lg p-2 text-gray-600 hover:bg-gray-100"
            onClick={() => setNavOpen((open) => !open)}
            aria-expanded={navOpen}
            aria-controls="docs-nav-mobile"
            aria-label="Daftar isi"
          >
            {navOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
          <Link href="/" className="flex items-center gap-2 min-w-0">
            <img src="/img/logo.webp" alt="" className="h-9 w-7 object-contain" />
            <span className="font-display text-lg font-extrabold tracking-tight text-primary-900 whitespace-nowrap">SMART-HUT</span>
          </Link>
          <span className="hidden sm:inline text-gray-300">/</span>
          <span className="hidden sm:inline text-sm font-semibold text-gray-700 whitespace-nowrap">Dokumentasi API</span>
          <span className="rounded-full bg-primary-100 px-2 py-0.5 text-xs font-bold text-primary-800">{version}</span>
          <div className="ml-auto">
            {auth?.user ? (
              <Link href={route('dashboard')} className="text-sm font-semibold text-primary-700 hover:text-primary-900 whitespace-nowrap">
                Ke aplikasi
              </Link>
            ) : (
              <Link href={route('login')} className="text-sm font-semibold text-primary-700 hover:text-primary-900">
                Masuk
              </Link>
            )}
          </div>
        </div>
        {navOpen && (
          <nav id="docs-nav-mobile" className="lg:hidden max-h-[70vh] overflow-y-auto border-t border-gray-200 bg-white px-4 py-3" aria-label="Daftar isi">
            {navLinks}
          </nav>
        )}
      </header>

      <div className="mx-auto flex max-w-7xl gap-10 px-4 sm:px-6 lg:px-8">
        <aside className="hidden lg:block w-56 shrink-0">
          <nav className="sticky top-16 max-h-[calc(100vh-4rem)] overflow-y-auto py-10" aria-label="Daftar isi">
            {navLinks}
          </nav>
        </aside>

        <main className="min-w-0 flex-1 max-w-3xl py-10 space-y-10">
          <div>
            <p className="text-sm font-semibold uppercase tracking-wider text-primary-700">API Integrasi Data</p>
            <h1 className="mt-1 font-display text-3xl sm:text-4xl font-extrabold tracking-tight text-gray-900">
              Membaca data kehutanan SMART-HUT dari sistem Anda
            </h1>
            <p className="mt-3 text-lg leading-8 text-gray-600">
              REST API baca-saja untuk sistem mitra Dinas Kehutanan Provinsi Jawa Timur. Respons berformat JSON, dan data yang dikirim
              hanya data yang sudah diverifikasi berjenjang (status final).
            </p>
          </div>

          <Section id="mulai" title="Mulai cepat" icon={BookOpen}>
            <ol className="list-decimal space-y-2 pl-5">
              <li>Minta admin SMART-HUT mendaftarkan sistem Anda sebagai klien integrasi dan menerbitkan token.</li>
              <li>Simpan token di variabel lingkungan atau secret manager, misalnya <code>SMARTHUT_TOKEN</code>.</li>
              <li>Uji koneksi dengan endpoint <code>GET /klien</code>, lalu ambil data sesuai akses token Anda.</li>
            </ol>
            <p className="font-semibold text-gray-900">Base URL</p>
            <CodeBlock title="Base URL" code={baseUrl} />
          </Section>

          <Section id="autentikasi" title="Autentikasi" icon={KeyRound}>
            <p>
              Setiap permintaan wajib membawa header <code>Authorization: Bearer &lt;token&gt;</code>. Token hanya ditampilkan sekali saat
              diterbitkan. Jika hilang, minta admin mencabutnya dan menerbitkan token baru.
            </p>
            <CodeBlock title="Header" code={'Authorization: Bearer 1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx\nAccept: application/json'} />
            <ul className="list-disc space-y-1 pl-5">
              <li>Jangan menaruh token di kode aplikasi yang berjalan di browser atau di repositori.</li>
              <li>Token bisa punya tanggal kedaluwarsa. Setelah lewat, permintaan ditolak dengan <code>401</code>.</li>
              <li>Respons selalu JSON, walaupun header <code>Accept</code> tidak dikirim.</li>
            </ul>
          </Section>

          <Section id="cakupan" title="Cakupan data" icon={MapPin}>
            <p>Cakupan data ditetapkan admin per klien dan tidak bisa diubah lewat parameter permintaan.</p>
            <Table
              head={['Cakupan', 'Data yang diterima']}
              rows={[
                [<strong key="p">Provinsi</strong>, 'Data final dari semua CDK di Jawa Timur.'],
                [<strong key="c">CDK</strong>, 'Hanya data final milik CDK klien. Filter cdk_id lain tidak membuka data CDK lain.'],
              ]}
            />
            <p>Data berstatus draft, menunggu verifikasi, atau ditolak tidak pernah dikirim. Kolom internal seperti identitas petugas dan catatan penolakan juga tidak ikut dikirim.</p>
          </Section>

          <Section id="akses" title="Akses data" icon={KeyRound}>
            <p>Setiap token membawa daftar akses (ability). Endpoint data mensyaratkan salah satunya. Token tanpa akses yang sesuai mendapat <code>403</code>.</p>
            <Table
              head={['Ability', 'Data']}
              rows={abilities.map((a) => [<code key="a" className="whitespace-nowrap text-gray-900">{a.value}</code>, a.label])}
            />
          </Section>

          <Section id="sinkronisasi" title="Pola sinkronisasi" icon={History}>
            <p>Untuk menyimpan salinan data di sistem Anda dan memperbaruinya secara berkala (misalnya setiap malam):</p>
            <ol className="list-decimal space-y-2 pl-5">
              <li>
                <strong>Pengambilan pertama:</strong> panggil endpoint daftar tanpa <code>updated_since</code>, ikuti <code>links.next</code> sampai
                bernilai <code>null</code>, lalu simpan data dengan <code>id</code> sebagai kunci. Catat <code>waktu_server</code> dari halaman pertama.
              </li>
              <li>
                <strong>Pengambilan berikutnya:</strong> panggil endpoint daftar dengan <code>updated_since=&lt;waktu_server tercatat&gt;</code>, lalu
                simpan atau timpa data berdasarkan <code>id</code>.
              </li>
              <li>
                <strong>Data yang ditarik:</strong> panggil <code>/deleted?since=&lt;waktu_server tercatat&gt;</code> dan hapus ID yang dikembalikan dari
                salinan Anda.
              </li>
              <li>Simpan <code>waktu_server</code> dari halaman pertama langkah 2 sebagai titik awal sinkronisasi berikutnya.</li>
            </ol>
            <p>
              Data diurutkan dari yang paling lama diperbarui, sehingga data yang berubah selama Anda membuka halaman demi halaman akan muncul
              di akhir dan tidak terlewat.
            </p>
          </Section>

          <Section id="endpoint" title="Endpoint">
            <p>
              Saat ini tersedia {endpoints.length} endpoint. Endpoint referensi wilayah, data master, dan rekap akan ditambahkan
              bertahap dan dicatat di <a href="#versi" className="font-semibold text-primary-700 underline-offset-2 hover:underline">riwayat versi</a>.
            </p>
            {groups.map((group) => (
              <div key={group.id} className="space-y-5">
                <h3 id={group.id} className="scroll-mt-24 pt-4 font-display text-xl font-bold text-gray-900">{group.name}</h3>
                {group.items.length > 2 && (
                  <ul className="rounded-xl bg-white p-4 ring-1 ring-gray-200 grid gap-1.5 text-sm">
                    {group.items.map((e) => (
                      <li key={e.route} className="flex flex-wrap items-center gap-2">
                        <MethodBadge method={e.method} />
                        <a href={`#endpoint-${e.route}`} className="font-mono text-primary-800 hover:underline break-all">{e.path}</a>
                        <span className="text-gray-500">{e.title}</span>
                      </li>
                    ))}
                  </ul>
                )}
                {group.items.map((endpoint) => (
                  <EndpointCard key={endpoint.route} endpoint={endpoint} baseUrl={baseUrl} abilityLabels={abilityLabels} />
                ))}
              </div>
            ))}
          </Section>

          <Section id="coba" title="Coba langsung" icon={Play}>
            <p>Tempel token untuk memeriksa koneksi dan konfigurasinya tanpa menulis kode.</p>
            <TryIt baseUrl={baseUrl} />
          </Section>

          <Section id="rate-limit" title="Rate limit" icon={Gauge}>
            <p>
              Setiap klien dibatasi <strong>{rateLimitPerMinute} permintaan per menit</strong>. Setiap respons membawa header
              <code> X-RateLimit-Limit</code> dan <code>X-RateLimit-Remaining</code>. Jika batas terlampaui, API mengembalikan
              <code> 429</code> dengan header <code>Retry-After</code> (detik). Tunggu selama itu sebelum mencoba lagi.
            </p>
          </Section>

          <Section id="error" title="Kode error" icon={AlertTriangle}>
            <p>Semua error memakai bentuk <code>{'{ "message": "..." }'}</code>. Error validasi menambahkan <code>errors</code> per parameter.</p>
            <Table
              head={['Status', 'Arti', 'Contoh']}
              rows={errors.map((e) => [
                <span key="s" className="font-bold text-gray-900">{e.status}</span>,
                e.meaning,
                <code key="c" className="block whitespace-pre-wrap break-all text-xs text-gray-600">{JSON.stringify(e.example)}</code>,
              ])}
            />
          </Section>

          <Section id="versi" title="Riwayat versi" icon={History}>
            <p>Perubahan yang tidak kompatibel akan dirilis sebagai versi baru (<code>/api/v2</code>). Versi lama tetap berjalan minimal 6 bulan.</p>
            <ul className="space-y-4">
              {changelog.map((entry) => (
                <li key={entry.version} className="rounded-xl bg-white p-4 ring-1 ring-gray-200">
                  <p className="font-semibold text-gray-900">
                    {entry.version} <span className="font-normal text-gray-500">· {entry.date}</span>
                  </p>
                  <ul className="mt-1 list-disc pl-5 text-gray-700">
                    {entry.changes.map((change) => <li key={change}>{change}</li>)}
                  </ul>
                </li>
              ))}
            </ul>
          </Section>

          <footer className="border-t border-gray-200 pt-6 text-sm text-gray-500">
            Dinas Kehutanan Provinsi Jawa Timur · Pertanyaan teknis dan permintaan token disampaikan ke admin SMART-HUT.
          </footer>
        </main>
      </div>
    </div>
  );
}
