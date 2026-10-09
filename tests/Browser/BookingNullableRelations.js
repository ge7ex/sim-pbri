async (page) => {
 // Run only against the documented isolated QA fixture, never the runtime server.
 if(!page.url().startsWith('http://127.0.0.1:8765/'))throw Error('Isolated QA origin required');
 const errors=[];const listener=e=>errors.push(e.message);page.on('pageerror',listener);
 await page.unrouteAll();await page.goto('http://127.0.0.1:8765/app');
 const variants=['transition','amendment','simulator','review'];const checks=[];
 for(const variant of variants){
  await page.goto('http://127.0.0.1:8765/app/bookings');
  const pattern=variant==='review'?'**/app/review':'**/app/bookings/1';
  await page.route(pattern,async route=>{
   const response=await route.fetch();
   if(!route.request().headers()['x-inertia']){await route.fulfill({response});return;}
   const alter=data=>{
    const b=variant==='review'?data.props.bookings.data[0]:data.props.booking;
    if(variant==='transition') b.status_transitions[0].actor=null;
    if(variant==='amendment') b.participant_amendments=[{id:999,participant_count:12,reason:'UAT nullable actor',created_at:'2026-10-09T00:00:00Z',actor:null}];
    if(variant==='simulator'||variant==='review') b.simulator_asset.simulator_type=null;
    return data;
   };
   await route.fulfill({response,json:alter(await response.json())});
  });
  if(variant==='review')await page.getByRole('link',{name:'ตรวจสอบคำขอ',exact:true}).click();
  else await page.locator('a[href="/app/bookings/1"]').click();
  await page.waitForTimeout(300);
  checks.push({variant,errors:[...errors],h1:await page.locator('main h1').count(),fallback:await page.getByText(/ไม่พบ.*เข้าถึงได้/).count()});
  errors.length=0;await page.unroute(pattern);
 }
 page.off('pageerror',listener);
 if(checks.some(c=>c.errors.length||c.h1!==1||c.fallback!==1))throw Error(JSON.stringify(checks));
 return {checks};
}
