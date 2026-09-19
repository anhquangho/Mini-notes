// Usage: node tests/http-smoke.mjs http://127.0.0.1:8091 /absolute/path/test-access.json
// Uses isolated HTTP sessions. Never reads or exports browser cookies.
import fs from 'node:fs/promises';
import assert from 'node:assert/strict';
const [baseUrl, accessPath] = process.argv.slice(2);
if (!baseUrl || !accessPath) throw new Error('URL and test-access.json required');
const access = JSON.parse(await fs.readFile(accessPath, 'utf8'));
let passed = 0;
function check(name, value) { assert.ok(value, name); passed++; console.log('PASS ' + name); }
const nonce = (html, name = 'mn_nonce') => {
  const value = html.match(new RegExp('name="' + name + '" value="([^"]+)"'));
  if (!value) throw new Error('Nonce field missing');
  return value[1];
};
function client() {
  const jar = new Map();
  return async (path, fields) => {
    const response = await fetch((/^https?:/.test(path) ? path : baseUrl.replace(/\/$/, '') + '/' + path.replace(/^\//, '')), {
      method: fields ? 'POST' : 'GET', redirect: 'manual',
      headers: { Cookie: [...jar].map(([k,v])=>k+'='+v).join('; '), ...(fields ? {'Content-Type':'application/x-www-form-urlencoded'} : {}) },
      body: fields ? new URLSearchParams(fields) : undefined
    });
    for (const cookie of response.headers.getSetCookie()) {
      const item = cookie.split(';')[0]; const eq = item.indexOf('=');
      jar.set(item.slice(0,eq), item.slice(eq+1));
    }
    return { status:response.status, location:response.headers.get('location'), html:await response.text() };
  };
}
async function signIn(username) {
  const c = client();
  const login = await c('/login/');
  const user = access.accounts.find(u=>u.username===username);
  const response = await c('/wp-admin/admin-post.php',{action:'mn_login',username:user.username,password:user.password,mn_nonce:nonce(login.html)});
  check(username+' login sets authenticated redirect', response.status===302 && response.location.includes('/dashboard/'));
  return c;
}
const guest = client();
check('Public Home responds', (await guest('/')).status===200);
check('About responds', (await guest('/about/')).status===200);
check('Single post responds', (await guest('/a-small-habit-a-clearer-day/')).status===200);
check('Unknown page returns 404', (await guest('/missing-study-page-404/')).status===404);
check('Guest dashboard redirects to login', (await guest('/dashboard/')).location?.includes('/login/'));
check('Guest REST notes denied', (await guest('/wp-json/wp/v2/notes')).status===401);
check('Public posts REST responds', (await guest('/wp-json/wp/v2/posts')).status===200);
const badLogin = await guest('/login/');
check('Login CSRF denied', (await guest('/wp-admin/admin-post.php',{action:'mn_login',username:'alice',password:'incorrect',mn_nonce:'bad'})).status===403);
check('Bad credentials redirect to generic error', (await guest('/wp-admin/admin-post.php',{action:'mn_login',username:'alice',password:'incorrect',mn_nonce:nonce(badLogin.html)})).location?.includes('login=failed'));
const alice = await signIn('alice');
const bob = await signIn('bob');
const newPage = await alice('/dashboard/?new=1');
const stamp = 'HTTP-TEST-' + Date.now();
const data = {action:'mn_save_note',note_id:'0',mn_nonce:nonce(newPage.html),title:stamp,content:"Tiếng Việt & quotes ' \" \\\nSecond line",priority:'high',status:'in-progress',category:'QA'};
check('Create missing nonce denied', (await alice('/wp-admin/admin-post.php',{...data,mn_nonce:''})).status===403);
check('Empty title rejected', (await alice('/wp-admin/admin-post.php',{...data,title:'   '})).status===400);
check('Invalid priority rejected', (await alice('/wp-admin/admin-post.php',{...data,priority:'critical'})).status===400);
check('Array title rejected without PHP error', (await alice('/wp-admin/admin-post.php',{...data,title:undefined,'title[]':'bad'})).status===400);
const created = await alice('/wp-admin/admin-post.php',data);
check('Create redirects after save', created.status===302 && created.location.includes('message=created'));
const id = new URL(created.location).searchParams.get('note');
const detail = await alice('/dashboard/?note='+id);
check('Saved title visible', detail.html.includes(stamp));
check('Vietnamese content survives', detail.html.includes('Tiếng Việt'));
check('Cross-owner URL read denied', (await bob('/dashboard/?note='+id)).status===403);
check('Guest direct URL redirects', (await guest('/dashboard/?note='+id)).status===302);
check('Search finds own note', (await alice('/dashboard/?q='+stamp)).html.includes(stamp));
check('Search hides other-owner note', !(await bob('/dashboard/?q='+stamp)).html.includes('note='+id));
const updated = await alice('/wp-admin/admin-post.php',{...data,note_id:id,mn_nonce:nonce(detail.html),title:stamp+' updated',content:'<script>alert(1)</script><b>Safe text</b>',status:'done'});
check('Update succeeds',updated.status===302 && updated.location.includes('message=updated'));
const after = await alice('/dashboard/?note='+id);
check('Updated title persists',after.html.includes(stamp+' updated'));
check('Injected script not rendered',!after.html.includes('<script>alert(1)</script>'));
check('High priority badge rendered',after.html.includes('High priority'));
const bNew = await bob('/dashboard/?new=1');
check('Forged edit with Bob nonce denied', (await bob('/wp-admin/admin-post.php',{...data,note_id:id,mn_nonce:nonce(bNew.html),title:'Hacked'})).status===403);
const deleteForm = after.html.match(/<form class="delete-form"[\s\S]*?<\/form>/)[0];
check('Forged delete with Alice nonce denied', (await bob('/wp-admin/admin-post.php',{action:'mn_delete_note',note_id:id,mn_nonce:nonce(deleteForm)})).status===403);
const deleted = await alice('/wp-admin/admin-post.php',{action:'mn_delete_note',note_id:id,mn_nonce:nonce(deleteForm)});
check('Delete redirects to success',deleted.status===302 && deleted.location.includes('message=deleted'));
check('Trashed note unavailable in workspace',(await alice('/dashboard/?note='+id)).status===404);
check('Malformed ID rejected',(await alice('/dashboard/?note=abc')).status===400);
const logoutPage = await alice('/dashboard/');
const logoutUrl = logoutPage.html.match(/href="([^"]*wp-login\.php\?action=logout[^"]*)"/)[1].replace(/&amp;|&#0?38;|&#x26;/gi,'&');
check('Logout redirects',(await alice(logoutUrl)).status===302);
check('After logout dashboard is protected',(await alice('/dashboard/')).location?.includes('/login/'));
console.log(passed+' HTTP checks passed. Test note is left in recoverable Trash.');
