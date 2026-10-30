import { Link, useNavigate } from 'react-router-dom';
import DispensationForm from '../components/DispensationForm';

export default function DispensationCreatePage() {
  const navigate = useNavigate();

  return (
    <>
      {/* Breadcrumbs */}
      <nav className="breadcrumbs text-sm mb-4" aria-label="Breadcrumb">
        <ul>
          <li><Link to="/dispensation">Dispensaciones</Link></li>
          <li className="text-base-content/70">Nueva</li>
        </ul>
      </nav>

      <h2 className="text-2xl font-bold mb-8">Nueva dispensación</h2>
      <DispensationForm onCreated={(id) => navigate(`/dispensation/${id}`)} />
    </>
  );
}
