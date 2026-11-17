#!/usr/bin/env bash
# HTTP checks against a local site. Usage: tests/http-checks.sh <base-url> <fixtures.json> <wp-path>
set -u
BASE="$1"; FX="$2"; WPP="$3"
j() { python3 -I -c "import json,sys;print(json.load(open(sys.argv[1]))[sys.argv[2]])" "$FX" "$1"; }
LOCKED=$(j locked); FREE=$(j free); EP=$(j episode); CN=$(j cookie_name)
MC="$CN=$(j member_cookie)"; FC="$CN=$(j free_cookie)"
fail=0
ok() { if [ "$1" = 0 ]; then echo "PASS $2"; else echo "FAIL $2"; fail=$((fail+1)); fi; }
has() { grep -q -- "$2" <<<"$1"; }
code() { curl -s -o /dev/null -w "%{http_code}" "$@"; }

KIDS=$(curl -s -D - "$BASE/kids-corner/")
has "$KIDS" "Content-Security-Policy: default-src 'self'"; ok $? "Kids Corner sends a same-site-only content policy"
has "$KIDS" "Referrer-Policy: no-referrer"; ok $? "Kids Corner sends no referrer"
! has "$KIDS" "WT-ANALYTICS-MARKER"; ok $? "Kids Corner loads no analytics"
! grep -Eiq "fonts.googleapis|fonts.gstatic|googletagmanager|facebook.net|youtube.com" <<<"$KIDS"; ok $? "Kids Corner calls no third-party service"
! has "$KIDS" "emoji"; ok $? "Kids Corner skips the emoji script"
has "$KIDS" "The Adventures of Abtuvi"; ok $? "Kids Corner shows The Adventures of Abtuvi"
has "$KIDS" "Being Different Is Special"; ok $? "Episode titles show"
has "$KIDS" "Abtuvi Starts School" && has "$KIDS" "Coming soon"; ok $? "Abtuvi Starts School shows as coming soon"
has "$KIDS" 'data-wt-grownup'; ok $? "Plans and locked items sit behind the grown-up check"
has "$KIDS" "badge lock"; ok $? "A guest sees members-only items as locked"
! grep -qi '<input' <<<"$(sed -n '/<main/,/<\/main>/p' <<<"$KIDS")"; ok $? "Kids Corner has no fields a child could type in"
has "$(curl -s "$BASE/kids-corner/?age=9-12")" 'data-age="9-12"[^>]*aria-current="page"'; ok $? "Age picker switches to 9 to 12"

[ "$(code "$BASE/wt-file/$LOCKED/")" = 302 ]; ok $? "Guest asking for a members-only PDF is sent to plans"
[ "$(code -b "$FC" "$BASE/wt-file/$LOCKED/")" = 302 ]; ok $? "Signed-in parent without a plan is sent to plans"
H=$(curl -s -D - -o /dev/null -b "$MC" "$BASE/wt-file/$LOCKED/")
has "$H" " 200" && has "$H" "Content-Type: application/pdf" && has "$H" "Cache-Control: private, no-store"; ok $? "Member gets the PDF, not cached"
H=$(curl -s -D - -o /dev/null "$BASE/wt-file/$FREE/")
has "$H" " 200" && has "$H" "application/pdf"; ok $? "Anyone gets the free sample PDF"
H=$(curl -s -D - -o /dev/null -H "Range: bytes=0-1023" "$BASE/wt-file/$EP/")
has "$H" " 206" && has "$H" "Content-Range: bytes 0-1023/" && has "$H" "video/mp4"; ok $? "Free Abtuvi preview streams with seeking"
[ "$(code "$BASE/wt-file/999999/")" = 404 ]; ok $? "Unknown file is a 404"

PAGE=$(curl -s "$BASE/kids-corner/printables/$(cd "$WPP" && wp --allow-root post get "$LOCKED" --field=post_name 2>/dev/null)/")
has "$PAGE" "wt-locked"; ok $? "Members-only printable page shows the locked card to a guest"
PAGE=$(curl -s -b "$MC" "$BASE/kids-corner/printables/$(cd "$WPP" && wp --allow-root post get "$LOCKED" --field=post_name 2>/dev/null)/")
! has "$PAGE" "wt-locked"; ok $? "Member sees the printable page itself"

REST=$(curl -s "$BASE/wp-json/wp/v2/wt_printable/$LOCKED")
has "$REST" '"wt_locked":true' && ! has "$REST" "_wt_file" && ! has "$REST" "test-$LOCKED.pdf"; ok $? "REST API hides locked content and file names"

MOMS=$(curl -s "$BASE/moms/")
has "$MOMS" "WT-ANALYTICS-MARKER"; ok $? "Moms pages can load analytics"
has "$MOMS" 'href="https://www.amazon.com/dp/B08YYVRXLM"'; ok $? "Amazon links carry no tag before the ID is added"
! has "$MOMS" "As an Amazon Associate"; ok $? "Disclosure hidden before the ID is added"
has "$MOMS" 'rel="sponsored nofollow noopener"'; ok $? "Product links are marked sponsored"
(cd "$WPP" && wp --allow-root eval '$o=get_option("wt_settings");$o["amazon_tag"]="testtag-20";update_option("wt_settings",$o);' >/dev/null 2>&1)
MOMS=$(curl -s "$BASE/moms/")
has "$MOMS" 'amazon.com/dp/B08YYVRXLM?tag=testtag-20' && has "$MOMS" "As an Amazon Associate"; ok $? "One setting adds the tag and the disclosure together"
(cd "$WPP" && wp --allow-root eval '$o=get_option("wt_settings");$o["amazon_tag"]="";update_option("wt_settings",$o);' >/dev/null 2>&1)

echo; [ $fail = 0 ] && echo "ALL PASSED" || echo "$fail FAILED"
exit $fail
