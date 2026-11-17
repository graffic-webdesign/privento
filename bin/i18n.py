#!/usr/bin/env python3
"""Regenerate Privento's translation files. No gettext tooling needed.

    python bin/i18n.py                       extract, merge, compile, check
    python bin/i18n.py --seed da_DK map.json fill empty msgstrs from {msgid: msgstr}

Steps: extract every translatable string from the PHP source into
languages/privento.pot, merge it into each .po (existing translations are kept,
strings no longer in the source are dropped), compile the .mo files, read them
back with Python's gettext to prove they parse, and check the translations.
Output is deterministic, so CI can run this and fail on a diff.

Exit code 1 when a translation is missing, has different placeholders than its
source, or when da_DK no longer reproduces the texts older versions stored.
"""
import gettext
import glob
import json
import os
import re
import struct
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
LANG = os.path.join(ROOT, 'languages')
DOMAIN = 'privento'
LOCALES = ['da_DK', 'de_DE', 'nb_NO', 'sv_SE']
# WordPress has no locale fallback: de_DE_formal needs a file of its own.
COPIES = {'de_DE_formal': 'de_DE'}
SKIP_DIRS = ('tests', 'docs', 'bin', '.git', '.github', '.superpowers', '.claude')

CALL = re.compile(
    r"\b(?:__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e)\(\s*"
    r"'((?:[^'\\]|\\.)*)'\s*,\s*'" + DOMAIN + r"'\s*\)"
)
PLACEHOLDER = re.compile(r'%(?:\d+\$)?[sdf]')


def extract():
    """{msgid: [file:line, ...]} for every string in the plugin's PHP."""
    found = {}
    for path in sorted(glob.glob(os.path.join(ROOT, '**', '*.php'), recursive=True)):
        rel = os.path.relpath(path, ROOT).replace(os.sep, '/')
        if rel.split('/')[0] in SKIP_DIRS:
            continue
        source = open(path, encoding='utf-8').read()
        for match in CALL.finditer(source):
            msgid = match.group(1).replace("\\'", "'").replace('\\\\', '\\')
            line = source.count('\n', 0, match.start()) + 1
            found.setdefault(msgid, []).append(f'{rel}:{line}')
    return found


def quote(text):
    return '"' + text.replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n') + '"'


def unquote(text):
    return re.sub(r'\\(.)', lambda m: {'n': '\n'}.get(m.group(1), m.group(1)), text[1:-1])


def read_po(path):
    """{msgid: msgstr} — enough of the format for files this script wrote."""
    entries, msgid, msgstr, target = {}, None, None, None
    if not os.path.exists(path):
        return entries
    for raw in open(path, encoding='utf-8'):
        line = raw.strip()
        if line.startswith('msgid '):
            if msgid:
                entries[msgid] = msgstr or ''
            msgid, msgstr, target = unquote(line[6:]), '', 'id'
        elif line.startswith('msgstr '):
            msgstr, target = unquote(line[7:]), 'str'
        elif line.startswith('"'):
            if target == 'id':
                msgid += unquote(line)
            elif target == 'str':
                msgstr += unquote(line)
    if msgid:
        entries[msgid] = msgstr or ''
    return entries


def header(locale=None):
    lines = [
        'Project-Id-Version: Privento',
        'Report-Msgid-Bugs-To: https://github.com/graffic-webdesign/privento',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'X-Domain: ' + DOMAIN,
    ]
    if locale:
        lines += ['Language: ' + locale, 'Plural-Forms: nplurals=2; plural=(n != 1);']
    return ''.join(line + '\n' for line in lines)


def write_po(path, strings, translations, locale=None):
    out = ['msgid ""', 'msgstr ""']
    out += [quote(line + '\n') for line in header(locale).splitlines()]
    for msgid in sorted(strings):
        out.append('')
        out += ['#: ' + ref for ref in strings[msgid]]
        out.append('msgid ' + quote(msgid))
        out.append('msgstr ' + quote(translations.get(msgid, '')))
    with open(path, 'w', encoding='utf-8', newline='\n') as handle:
        handle.write('\n'.join(out) + '\n')


def write_mo(path, translations, locale):
    """GNU .mo: magic, revision, count, two offset tables, then the strings."""
    catalog = {'': header(locale)}
    catalog.update({k: v for k, v in translations.items() if v})
    keys = sorted(catalog)
    ids = [k.encode('utf-8') for k in keys]
    strs = [catalog[k].encode('utf-8') for k in keys]

    count = len(keys)
    id_table = 7 * 4
    str_table = id_table + count * 8
    offset = str_table + count * 8
    id_entries, str_entries, blob = [], [], b''
    for chunk_list, entries in ((ids, id_entries), (strs, str_entries)):
        for chunk in chunk_list:
            entries.append((len(chunk), offset + len(blob)))
            blob += chunk + b'\0'

    data = struct.pack('<Iiiiiii', 0x950412DE, 0, count, id_table, str_table, 0, 0)
    for length, position in id_entries + str_entries:
        data += struct.pack('<ii', length, position)
    with open(path, 'wb') as handle:
        handle.write(data + blob)


def legacy_danish():
    """{english default: danish text 1.3.8-1.6.x stored}, read from the PHP."""
    def pairs(path, pattern):
        return dict(re.findall(pattern, open(os.path.join(ROOT, path), encoding='utf-8').read()))
    english = pairs('includes/Core/Settings.php', r"'(\w+)'\s*=> __\( '((?:[^'\\]|\\.)*)', 'privento' \)")
    block = re.search(r'LEGACY_DA = \[(.*?)\];', open(os.path.join(ROOT, 'includes/Core/Installer.php'), encoding='utf-8').read(), re.S).group(1)
    danish = dict(re.findall(r"'(\w+)'\s*=> '((?:[^'\\]|\\.)*)'", block))
    return {english[key]: text for key, text in danish.items()}


def main():
    sys.stdout.reconfigure(encoding='utf-8')  # Windows consoles default to cp1252.
    seeds = {}
    if '--seed' in sys.argv:
        at = sys.argv.index('--seed')
        seeds[sys.argv[at + 1]] = json.load(open(sys.argv[at + 2], encoding='utf-8'))

    strings = extract()
    write_po(os.path.join(LANG, DOMAIN + '.pot'), strings, {})
    problems = []

    for locale in LOCALES:
        po = os.path.join(LANG, f'{DOMAIN}-{locale}.po')
        mo = os.path.join(LANG, f'{DOMAIN}-{locale}.mo')
        old = read_po(po)
        translations = {m: old.get(m) or seeds.get(locale, {}).get(m, '') for m in strings}
        write_po(po, strings, translations, locale)
        write_mo(mo, translations, locale)
        for alias in [a for a, source in COPIES.items() if source == locale]:
            write_mo(os.path.join(LANG, f'{DOMAIN}-{alias}.mo'), translations, alias)

        # Read back with a real parser rather than trusting the byte packing.
        compiled = gettext.GNUTranslations(open(mo, 'rb'))
        for msgid, msgstr in translations.items():
            if not msgstr:
                problems.append(f'{locale}: untranslated: {msgid[:70]}')
            elif compiled.gettext(msgid) != msgstr:
                problems.append(f'{locale}: .mo does not round-trip: {msgid[:70]}')
            elif sorted(PLACEHOLDER.findall(msgid)) != sorted(PLACEHOLDER.findall(msgstr)):
                problems.append(f'{locale}: placeholders differ: {msgid[:70]}')
            elif msgid.count('<a ') != msgstr.count('<a '):
                problems.append(f'{locale}: links differ: {msgid[:70]}')

        if locale == 'da_DK':
            for english, danish in legacy_danish().items():
                if translations.get(english) != danish:
                    problems.append(f'da_DK: must stay "{danish[:50]}" (stored by 1.6.x): {english[:50]}')

    print(f'{len(strings)} strings, {len(LOCALES)} locales')
    for problem in problems:
        print('  ' + problem)
    sys.exit(1 if problems else 0)


if __name__ == '__main__':
    main()
