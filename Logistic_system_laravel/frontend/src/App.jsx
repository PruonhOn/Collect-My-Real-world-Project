export default function App() {
  return (
    <div style={{ padding: '20px', fontFamily: 'sans-serif' }}>
      <header style={{ borderBottom: '1px solid #ccc', marginBottom: '20px' }}>
        <h1>🚚 Logistics System Management</h1>
      </header>

      <main>
        <section style={{ backgroundColor: '#f4f4f4', padding: '15px', borderRadius: '8px' }}>
          <h2>Dashboard Overview</h2>
          <p>ស្ថានភាពប្រព័ន្ធ៖ <span style={{ color: 'green' }}>កំពុងដំណើរការ (Running)</span></p>
          
          <div style={{ display: 'flex', gap: '20px', marginTop: '10px' }}>
            <div style={{ border: '1px solid #ddd', padding: '10px', flex: 1 }}>
              <h3>ការដឹកជញ្ជូន</h3>
              <p>សរុប៖ ០</p>
            </div>
            <div style={{ border: '1px solid #ddd', padding: '10px', flex: 1 }}>
              <h3>ទំនិញក្នុងស្តុក</h3>
              <p>សរុប៖ ០</p>
            </div>
          </div>
        </section>

        <section style={{ marginTop: '20px' }}>
          <h3>តេស្តការភ្ជាប់ទិន្នន័យ (Test Connection)</h3>
          <p>ប្រសិនបើអ្នកឃើញសារនេះ មានន័យថា Frontend របស់អ្នកដើរហើយ!</p>
        </section>
      </main>
    </div>
    console.log('App component rendered successfully')
  );
}