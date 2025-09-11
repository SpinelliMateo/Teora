import qz from "qz-tray";

export function useQzTray() {
  const imprimirConQZTray = async (zpl: string): Promise<void> => {
    const withTimeout = <T,>(p: Promise<T>, ms = 8000) => {
      return Promise.race([
        p,
        new Promise<T>((_, rej) => setTimeout(() => rej(new Error('Timeout')), ms))
      ]);
    };

    try {
      qz.security.setCertificatePromise(function(resolve: any, reject: any) {
        fetch('/qz/digital-certificate.txt', {
          cache: 'no-store', 
          headers: {'Content-Type': 'text/plain'}
        })
        .then(async function(data) { 
          data.ok ? resolve(await data.text()) : reject(await data.text()); 
        });
      });

      qz.security.setSignatureAlgorithm('SHA256');
      qz.security.setSignaturePromise(function (toSign: string) {
        return function (resolve: any, reject: any) {
          const url = window.location.origin + '/qz/sign';
          fetch(url, {
            method: 'POST',
            cache: 'no-store',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
            },
            body: JSON.stringify({ toSign })
          })
          .then(res => {
            if (!res.ok) return res.text().then(t => reject(t));
            return res.json();
          })
          .then(json => {
            if (json && json.signature) resolve(json.signature);
            else reject(json && json.error ? json.error : 'No signature');
          })
          .catch(err => {
            reject(err && err.message ? err.message : err);
          });
        };
      });

      await withTimeout(qz.websocket.connect(), 8000);

      const rawPrinter = await withTimeout(qz.printers.find('HPRT HT800'), 5000);
      const printer = Array.isArray(rawPrinter) ? rawPrinter[0] : rawPrinter;

      const cfg = qz.configs.create(printer as string);
      await withTimeout(qz.print(cfg, [zpl]), 8000);

    } catch (err: unknown) {
      console.error('Error al imprimir con QZ Tray:', err);
      if (err instanceof Error) {
        console.error('Mensaje del error:', err.message);
        console.error('Stack trace:', err.stack);
      }
      throw err;
    } finally {
      try {
        if (qz.websocket.isActive()) {
          qz.websocket.disconnect();
          console.log('Desconectado de QZ Tray');
        }
      } catch (e) {
        console.warn('Error al desconectar QZ Tray', e);
      }
    }
  };

  const imprimirMultiplesConQZTray = async (zpls: string[]): Promise<void> => {
    if (!zpls || zpls.length === 0) {
      throw new Error('No hay etiquetas para imprimir');
    }

    // Si solo hay una etiqueta, usar la función existente
    if (zpls.length === 1) {
      return imprimirConQZTray(zpls[0]);
    }

    const withTimeout = <T,>(p: Promise<T>, ms = 8000) => {
      return Promise.race([
        p,
        new Promise<T>((_, rej) => setTimeout(() => rej(new Error('Timeout')), ms))
      ]);
    };

    try {
      // Configurar certificado y firma una sola vez
      qz.security.setCertificatePromise(function(resolve: any, reject: any) {
        fetch('/qz/digital-certificate.txt', {
          cache: 'no-store', 
          headers: {'Content-Type': 'text/plain'}
        })
        .then(async function(data) { 
          data.ok ? resolve(await data.text()) : reject(await data.text()); 
        });
      });

      qz.security.setSignatureAlgorithm('SHA256');
      qz.security.setSignaturePromise(function (toSign: string) {
        return function (resolve: any, reject: any) {
          const url = window.location.origin + '/qz/sign';
          fetch(url, {
            method: 'POST',
            cache: 'no-store',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).content
            },
            body: JSON.stringify({ toSign })
          })
          .then(res => {
            if (!res.ok) return res.text().then(t => reject(t));
            return res.json();
          })
          .then(json => {
            if (json && json.signature) resolve(json.signature);
            else reject(json && json.error ? json.error : 'No signature');
          })
          .catch(err => {
            reject(err && err.message ? err.message : err);
          });
        };
      });

      // Conectar una sola vez
      await withTimeout(qz.websocket.connect(), 8000);

      const rawPrinter = await withTimeout(qz.printers.find('HPRT HT800'), 5000);
      const printer = Array.isArray(rawPrinter) ? rawPrinter[0] : rawPrinter;

      const cfg = qz.configs.create(printer as string);
      
      // Imprimir todas las etiquetas de una vez
      console.log(`Imprimiendo ${zpls.length} etiquetas...`);
      await withTimeout(qz.print(cfg, zpls), 15000); // Timeout más largo para múltiples etiquetas

      console.log(`${zpls.length} etiquetas enviadas a la impresora ✅`);

    } catch (err: unknown) {
      console.error('Error al imprimir múltiples etiquetas con QZ Tray:', err);
      if (err instanceof Error) {
        console.error('Mensaje del error:', err.message);
        console.error('Stack trace:', err.stack);
      }
      throw err;
    } finally {
      try {
        if (qz.websocket.isActive()) {
          qz.websocket.disconnect();
          console.log('Desconectado de QZ Tray');
        }
      } catch (e) {
        console.warn('Error al desconectar QZ Tray', e);
      }
    }
  };

  return {
    imprimirConQZTray,
    imprimirMultiplesConQZTray
  };
}