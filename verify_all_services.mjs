import { execSync } from 'child_process';

console.log('=== VERIFYING ALL ECO SERVICES ===\n');

try {
  console.log('[1/3] Running POS Test Suite...');
  const posOut = execSync('npm --prefix POS test', { encoding: 'utf-8' });
  console.log('POS Test Output:\n' + posOut.split('\n').slice(-10).join('\n'));

  console.log('[2/3] Running Logistika Test Suite...');
  const logistikaOut = execSync('npm --prefix Logistika test', { encoding: 'utf-8' });
  console.log('Logistika Test Output:\n' + logistikaOut.split('\n').slice(-10).join('\n'));

  console.log('[3/3] Running Marketplace Unit Test...');
  const mpOut = execSync('php Marketplace/tests/MarketplaceConsistencyTest.php', { encoding: 'utf-8' });
  console.log('Marketplace Test Output:\n' + mpOut);

  console.log('\n=== ALL LOCAL SERVICE TEST SUITES PASSED VERIFICATION! ===');
} catch (error) {
  console.error('\n❌ VERIFICATION FAILED:');
  console.error(error.stdout || error.message);
  process.exit(1);
}
